<?php
namespace app\common;

use think\facade\Cache;

/**
 * 登录滑块拼图验证（GD 出图，服务端校验）
 */
class SlideCaptcha
{
    public const BG_W = 310;
    public const BG_H = 155;
    public const PIECE = 47;
    public const TOLERANCE = 6;
    public const GEN_TTL = 300;
    public const TOKEN_TTL = 180;

    public static function generate(): array
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('服务器未启用 GD 扩展');
        }

        $bw = self::BG_W;
        $bh = self::BG_H;
        $ps = self::PIECE;
        $x = random_int((int) ($ps * 0.7), $bw - $ps - 12);
        $y = random_int(12, $bh - $ps - 12);

        $bg = imagecreatetruecolor($bw, $bh);
        imagealphablending($bg, true);
        imagesavealpha($bg, true);

        // 渐变底 + 色块干扰
        $c1 = [random_int(40, 90), random_int(90, 150), random_int(180, 240)];
        $c2 = [random_int(20, 70), random_int(50, 110), random_int(140, 200)];
        for ($i = 0; $i < $bh; $i++) {
            $t = $i / max(1, $bh - 1);
            $r = (int) ($c1[0] + ($c2[0] - $c1[0]) * $t);
            $g = (int) ($c1[1] + ($c2[1] - $c1[1]) * $t);
            $b = (int) ($c1[2] + ($c2[2] - $c1[2]) * $t);
            $col = imagecolorallocate($bg, $r, $g, $b);
            imageline($bg, 0, $i, $bw, $i, $col);
        }
        for ($n = 0; $n < 18; $n++) {
            $col = imagecolorallocatealpha(
                $bg,
                random_int(80, 255),
                random_int(80, 255),
                random_int(80, 255),
                random_int(60, 100)
            );
            imagefilledellipse(
                $bg,
                random_int(0, $bw),
                random_int(0, $bh),
                random_int(20, 70),
                random_int(20, 70),
                $col
            );
        }
        for ($n = 0; $n < 40; $n++) {
            $col = imagecolorallocate($bg, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imageline($bg, random_int(0, $bw), random_int(0, $bh), random_int(0, $bw), random_int(0, $bh), $col);
        }

        $mask = self::pieceMask($ps);
        $piece = imagecreatetruecolor($ps, $ps);
        imagealphablending($piece, false);
        imagesavealpha($piece, true);
        $transparent = imagecolorallocatealpha($piece, 0, 0, 0, 127);
        imagefill($piece, 0, 0, $transparent);

        $hole = imagecolorallocatealpha($bg, 0, 0, 0, 70);
        for ($py = 0; $py < $ps; $py++) {
            for ($px = 0; $px < $ps; $px++) {
                if (!$mask[$py][$px]) {
                    continue;
                }
                $sx = $x + $px;
                $sy = $y + $py;
                if ($sx < 0 || $sy < 0 || $sx >= $bw || $sy >= $bh) {
                    continue;
                }
                $rgb = imagecolorat($bg, $sx, $sy);
                imagesetpixel($piece, $px, $py, $rgb);
                imagesetpixel($bg, $sx, $sy, $hole);
            }
        }

        // 缺口描边
        $edge = imagecolorallocate($bg, 255, 255, 255);
        for ($py = 0; $py < $ps; $py++) {
            for ($px = 0; $px < $ps; $px++) {
                if (!$mask[$py][$px]) {
                    continue;
                }
                $border = false;
                foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
                    $nx = $px + $dx;
                    $ny = $py + $dy;
                    if ($nx < 0 || $ny < 0 || $nx >= $ps || $ny >= $ps || !$mask[$ny][$nx]) {
                        $border = true;
                        break;
                    }
                }
                if ($border) {
                    imagesetpixel($bg, $x + $px, $y + $py, $edge);
                }
            }
        }

        $key = bin2hex(random_bytes(16));
        Cache::set('slide_captcha:'.$key, [
            'x' => $x,
            'y' => $y,
            'ip' => self::clientIp(),
        ], self::GEN_TTL);

        return [
            'captcha_key' => $key,
            'bg' => self::toPngDataUri($bg),
            'slice' => self::toPngDataUri($piece),
            'y' => $y,
            'bg_w' => $bw,
            'bg_h' => $bh,
            'piece' => $ps,
        ];
    }

    public static function check(string $key, float $x): ?string
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        $cacheKey = 'slide_captcha:'.$key;
        $row = Cache::get($cacheKey);
        Cache::delete($cacheKey);
        if (!is_array($row) || !isset($row['x'])) {
            return null;
        }
        if (abs((float) $row['x'] - $x) > self::TOLERANCE) {
            return null;
        }
        $token = bin2hex(random_bytes(20));
        Cache::set('slide_captcha_ok:'.$token, [
            'at' => time(),
            'ip' => self::clientIp(),
        ], self::TOKEN_TTL);

        return $token;
    }

    public static function consumeToken(?string $token): bool
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }
        $cacheKey = 'slide_captcha_ok:'.$token;
        $row = Cache::get($cacheKey);
        Cache::delete($cacheKey);
        return is_array($row);
    }

    /** @return array<int, array<int, bool>> */
    private static function pieceMask(int $size): array
    {
        $mask = array_fill(0, $size, array_fill(0, $size, false));
        $r = (int) ($size / 2);
        $cx = $r;
        $cy = $r;
        $bump = (int) ($size * 0.18);
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $in = abs($x - $cx) <= $r - 2 && abs($y - $cy) <= $r - 2;
                // 右侧凸起
                $dx = $x - ($size - 2);
                $dy = $y - $cy;
                if (($dx * $dx + $dy * $dy) <= $bump * $bump) {
                    $in = true;
                }
                // 顶部凹槽
                $dx2 = $x - $cx;
                $dy2 = $y - 2;
                if (($dx2 * $dx2 + $dy2 * $dy2) <= ($bump - 1) * ($bump - 1) && $y < $cy) {
                    $in = false;
                }
                $mask[$y][$x] = $in;
            }
        }
        return $mask;
    }

    private static function toPngDataUri($im): string
    {
        ob_start();
        imagepng($im);
        $bin = ob_get_clean();
        imagedestroy($im);
        return 'data:image/png;base64,'.base64_encode((string) $bin);
    }

    private static function clientIp(): string
    {
        try {
            return (string) (request()->ip() ?: '');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

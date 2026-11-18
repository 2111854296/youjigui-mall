<?php
namespace app\model;
class Ad extends BaseModel {
  protected $name = 'ads';
  public static function activeQuery(){ return static::where('is_active',1); }

  public static function usableImage(?string $src): bool
  {
      $s = (string) $src;
      if ($s === '') {
          return false;
      }
      if (preg_match('/\.svg($|\?)/i', $s)) {
          return false;
      }
      if (stripos($s, 'placehold') !== false) {
          return false;
      }
      return true;
  }

  public static function toSlideArray($ad): array
  {
      $content = $ad->content ?? null;
      // 导入时写入的 origin: 仅供溯源，不对前台展示
      if (is_string($content) && strncasecmp($content, 'origin:', 7) === 0) {
          $content = null;
      }
      $title = (string) ($ad->title ?? '');
      if (preg_match('/^轮播\s*\d+$/u', trim($title))) {
          $title = '';
      }

      return [
          'id' => (int) $ad->id,
          'title' => $title,
          'image' => $ad->image,
          'link' => $ad->link ?: '',
          'sort' => (int) ($ad->sort ?? 0),
          'position' => $ad->position ?? '',
          'type' => $ad->type ?? 'image',
          'content' => $content,
          'price' => isset($ad->price) && $ad->price !== null ? (string) $ad->price : null,
      ];
  }

  public static function slides(string $position, int $limit = 8): array
  {
      $now = date('Y-m-d H:i:s');
      $rows = static::where('is_active', 1)
          ->where('position', $position)
          ->order('sort', 'asc')
          ->limit($limit)
          ->select();
      $out = [];
      foreach ($rows as $ad) {
          if (!static::usableImage($ad->image)) {
              continue;
          }
          if ($ad->starts_at && $ad->starts_at > $now) {
              continue;
          }
          if ($ad->ends_at && $ad->ends_at < $now) {
              continue;
          }
          $slide = static::toSlideArray($ad);
          // 无标题且无链接的坑位不进轮播，避免可点无跳转
          if (trim((string) ($slide['title'] ?? '')) === '' && trim((string) ($slide['link'] ?? '')) === '') {
              continue;
          }
          $out[] = $slide;
      }
      return $out;
  }
}
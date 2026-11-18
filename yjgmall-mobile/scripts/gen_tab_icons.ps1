# Generate proper tab bar icons + placeholder for yjgmall-mobile H5
Add-Type -AssemblyName System.Drawing

$tabDir = "C:\Users\Administrator\Desktop\有机硅商城\yjgmall-mobile\src\static\tab"
$staticDir = "C:\Users\Administrator\Desktop\有机硅商城\yjgmall-mobile\src\static"
$size = 81
$pad = 14

function New-Gfx([System.Drawing.Color]$color) {
  $bmp = New-Object System.Drawing.Bitmap $size, $size
  $g = [System.Drawing.Graphics]::FromImage($bmp)
  $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
  $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
  $g.Clear([System.Drawing.Color]::Transparent)
  $pen = New-Object System.Drawing.Pen $color, 5
  $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
  $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
  $pen.LineJoin = [System.Drawing.Drawing2D.LineJoin]::Round
  $brush = New-Object System.Drawing.SolidBrush $color
  return @{ bmp = $bmp; g = $g; pen = $pen; brush = $brush }
}

function Save-Icon($name, [ScriptBlock]$draw, [System.Drawing.Color]$color) {
  $ctx = New-Gfx $color
  & $draw $ctx.g $ctx.pen $ctx.brush
  $path = Join-Path $tabDir $name
  $ctx.bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
  $ctx.g.Dispose(); $ctx.pen.Dispose(); $ctx.brush.Dispose(); $ctx.bmp.Dispose()
  Write-Host "ok $name $((Get-Item $path).Length)"
}

$gray = [System.Drawing.Color]::FromArgb(255, 102, 102, 102)
$blue = [System.Drawing.Color]::FromArgb(255, 39, 105, 220)

# Home: house
$drawHome = {
  param($g,$pen,$brush)
  $pts = @(
    (New-Object System.Drawing.Point 40, 16),
    (New-Object System.Drawing.Point 16, 36),
    (New-Object System.Drawing.Point 24, 36),
    (New-Object System.Drawing.Point 24, 62),
    (New-Object System.Drawing.Point 36, 62),
    (New-Object System.Drawing.Point 36, 48),
    (New-Object System.Drawing.Point 46, 48),
    (New-Object System.Drawing.Point 46, 62),
    (New-Object System.Drawing.Point 58, 62),
    (New-Object System.Drawing.Point 58, 36),
    (New-Object System.Drawing.Point 66, 36)
  )
  $g.DrawLines($pen, $pts)
}

# Sell: box / package
$drawSell = {
  param($g,$pen,$brush)
  $g.DrawRectangle($pen, 20, 28, 42, 34)
  $g.DrawLine($pen, 20, 40, 62, 40)
  $g.DrawLine($pen, 41, 28, 41, 62)
  $g.DrawLine($pen, 28, 28, 41, 18)
  $g.DrawLine($pen, 54, 28, 41, 18)
}

# Buy: clipboard / list
$drawBuy = {
  param($g,$pen,$brush)
  $g.DrawRectangle($pen, 22, 20, 38, 48)
  $g.DrawRectangle($pen, 32, 14, 18, 10)
  $g.DrawLine($pen, 30, 36, 52, 36)
  $g.DrawLine($pen, 30, 46, 52, 46)
  $g.DrawLine($pen, 30, 56, 46, 56)
}

# Company: building
$drawCompany = {
  param($g,$pen,$brush)
  $g.DrawRectangle($pen, 22, 22, 38, 42)
  $g.DrawLine($pen, 22, 32, 60, 32)
  # windows
  $g.DrawRectangle($pen, 28, 38, 8, 8)
  $g.DrawRectangle($pen, 44, 38, 8, 8)
  $g.DrawRectangle($pen, 28, 50, 8, 8)
  $g.DrawRectangle($pen, 44, 50, 8, 8)
  $g.DrawLine($pen, 38, 54, 44, 54)
  $g.DrawLine($pen, 41, 54, 41, 64)
}

# Mine: person
$drawMine = {
  param($g,$pen,$brush)
  $g.DrawEllipse($pen, 30, 14, 22, 22)
  $g.DrawArc($pen, 18, 40, 46, 36, 200, 140)
}

foreach ($pair in @(
  @{n='home.png'; d=$drawHome; c=$gray},
  @{n='home-active.png'; d=$drawHome; c=$blue},
  @{n='sell.png'; d=$drawSell; c=$gray},
  @{n='sell-active.png'; d=$drawSell; c=$blue},
  @{n='buy.png'; d=$drawBuy; c=$gray},
  @{n='buy-active.png'; d=$drawBuy; c=$blue},
  @{n='company.png'; d=$drawCompany; c=$gray},
  @{n='company-active.png'; d=$drawCompany; c=$blue},
  @{n='mine.png'; d=$drawMine; c=$gray},
  @{n='mine-active.png'; d=$drawMine; c=$blue}
)) {
  Save-Icon $pair.n $pair.d $pair.c
}

# Better product placeholder (light gray tile with image mark)
$ph = New-Object System.Drawing.Bitmap 200, 200
$pg = [System.Drawing.Graphics]::FromImage($ph)
$pg.SmoothingMode = 'AntiAlias'
$pg.Clear([System.Drawing.Color]::FromArgb(255, 243, 245, 249))
$pPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(255, 180, 188, 200), 4)
$pPen.StartCap = 'Round'; $pPen.EndCap = 'Round'
# mountain / image icon
$pg.DrawRectangle($pPen, 50, 50, 100, 100)
$pts = @(
  (New-Object System.Drawing.Point 62, 130),
  (New-Object System.Drawing.Point 95, 90),
  (New-Object System.Drawing.Point 115, 112),
  (New-Object System.Drawing.Point 138, 78),
  (New-Object System.Drawing.Point 138, 130),
  (New-Object System.Drawing.Point 62, 130)
)
$pg.DrawLines($pPen, $pts)
$pg.DrawEllipse($pPen, 70, 68, 18, 18)
$phPath = Join-Path $staticDir "placeholder.png"
$ph.Save($phPath, [System.Drawing.Imaging.ImageFormat]::Png)
$pg.Dispose(); $pPen.Dispose(); $ph.Dispose()
Write-Host "ok placeholder.png $((Get-Item $phPath).Length)"

# Remove test file if any
$test = Join-Path $tabDir "_test.png"
if (Test-Path $test) { Remove-Item $test -Force }

Write-Host DONE

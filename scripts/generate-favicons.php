<?php

// One-off GD renderer for the geometry in public/favicon.svg; no package dependency.
function fillRoundedRect(GdImage $image, int $left, int $top, int $right, int $bottom, int $radius, int $color): void
{
    imagefilledrectangle($image, $left + $radius, $top, $right - $radius, $bottom, $color);
    imagefilledrectangle($image, $left, $top + $radius, $right, $bottom - $radius, $color);

    foreach ([[$left + $radius, $top + $radius], [$right - $radius, $top + $radius], [$left + $radius, $bottom - $radius], [$right - $radius, $bottom - $radius]] as [$x, $y]) {
        imagefilledellipse($image, $x, $y, $radius * 2, $radius * 2, $color);
    }
}

function renderPng(int $size): string
{
    $scale = 4;
    $large = imagecreatetruecolor($size * $scale, $size * $scale);
    imagealphablending($large, false);
    imagesavealpha($large, true);
    imagefill($large, 0, 0, imagecolorallocatealpha($large, 0, 0, 0, 127));
    imagealphablending($large, true);

    $unit = $size * $scale / 32;
    $coord = static fn (float $value): int => (int) round($value * $unit);
    $indigo = imagecolorallocate($large, 79, 70, 229);
    $white = imagecolorallocate($large, 255, 255, 255);

    fillRoundedRect($large, $coord(1), $coord(1), $coord(31), $coord(31), $coord(7), $indigo);
    fillRoundedRect($large, $coord(5), $coord(7), $coord(27), $coord(23), $coord(5), $white);
    imagefilledpolygon($large, [$coord(11), $coord(20), $coord(17), $coord(22), $coord(11), $coord(27)], $white);

    foreach ([11.5, 16, 20.5] as $x) {
        imagefilledellipse($large, $coord($x), $coord(15), $coord(2.4), $coord(2.4), $indigo);
    }

    $small = imagecreatetruecolor($size, $size);
    imagealphablending($small, false);
    imagesavealpha($small, true);
    imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
    imagecopyresampled($small, $large, 0, 0, 0, 0, $size, $size, $size * $scale, $size * $scale);

    ob_start();
    imagepng($small);
    $png = ob_get_clean();
    imagedestroy($small);
    imagedestroy($large);

    return $png;
}

$public = dirname(__DIR__).'/public';
$frames = [];
foreach ([16, 32, 48] as $size) {
    $frames[$size] = renderPng($size);
}

$header = pack('vvv', 0, 1, count($frames));
$offset = 6 + count($frames) * 16;
$payload = '';
foreach ($frames as $size => $png) {
    $header .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
    $payload .= $png;
    $offset += strlen($png);
}

if (file_put_contents($public.'/favicon.ico', $header.$payload) === false
    || file_put_contents($public.'/apple-touch-icon.png', renderPng(180)) === false) {
    exit(1);
}

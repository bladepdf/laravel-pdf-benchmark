<?php

declare(strict_types=1);

$target = dirname(__DIR__).'/public/images/benchmark-logo.png';
if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0775, true);
}

$image = imagecreatetruecolor(320, 96);
imagesavealpha($image, true);
$transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
imagefill($image, 0, 0, $transparent);
$navy = imagecolorallocate($image, 17, 32, 71);
$blue = imagecolorallocate($image, 37, 87, 214);
$white = imagecolorallocate($image, 255, 255, 255);

imagefilledellipse($image, 48, 48, 76, 76, $blue);
imagefilledpolygon($image, [48, 16, 56, 39, 80, 39, 61, 53, 68, 76, 48, 62, 28, 76, 35, 53, 16, 39, 40, 39], $white);
imagestring($image, 5, 100, 31, 'NORTHSTAR RESEARCH', $navy);
imagestring($image, 2, 101, 55, 'DETERMINISTIC FIXTURE', $blue);

imagepng($image, $target, 9);
imagedestroy($image);

echo "Generated {$target}\n";

<?php

$dir = __DIR__ . '/../storage/app/public/raw_uploads';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$colors = [
    [220, 38, 38], [217, 119, 6], [202, 138, 4], [22, 163, 74],
    [13, 148, 136], [2, 132, 199], [79, 70, 229], [147, 51, 234],
    [219, 39, 119], [71, 85, 105], [234, 88, 12], [5, 150, 105]
];

foreach ($colors as $i => $c) {
    $im = imagecreatetruecolor(800, 1200);
    $col = imagecolorallocate($im, $c[0], $c[1], $c[2]);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $col);
    imagestring($im, 5, 250, 580, 'GALADYA SHOT #' . ($i + 1), $white);
    imagestring($im, 3, 260, 620, '1200x800 Self Shoot High-Res', $white);
    imagejpeg($im, $dir . '/sample_shot_' . ($i + 1) . '.jpg', 92);
    imagedestroy($im);
}

echo "12 sample photos successfully generated in {$dir}!\n";


<?php

$dir = __DIR__ . '/../public/images';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$w = 800;
$h = 240;

// Dark logo version (for light backgrounds)
$img = imagecreatetruecolor($w, $h);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$gold = imagecolorallocate($img, 212, 160, 23);
$dark = imagecolorallocate($img, 15, 23, 42);
$gray = imagecolorallocate($img, 100, 116, 139);

// Draw camera lens aperture graphic
imagefilledellipse($img, 100, 120, 110, 110, $dark);
imagefilledellipse($img, 100, 120, 90, 90, $transparent);
imagefilledellipse($img, 100, 120, 40, 40, $gold);
imagefilledellipse($img, 100, 120, 20, 20, $dark);

// Text rendering
imagestring($img, 5, 180, 70, 'G A L A D Y A   S T U D I O', $dark);
imagestring($img, 5, 180, 71, 'G A L A D Y A   S T U D I O', $dark);
imagestring($img, 3, 182, 115, 'S E L F - S H O O T  &  P H O T O P R I N T', $gold);
imagestring($img, 2, 182, 142, 'P H I L I P P I N E S  *  E S T . 2 0 2 4', $gray);

imagepng($img, $dir . '/galadya-logo.png');
imagedestroy($img);

// Light logo version (for dark backgrounds)
$imgLight = imagecreatetruecolor($w, $h);
imagesavealpha($imgLight, true);
imagefill($imgLight, 0, 0, $transparent);
$white = imagecolorallocate($imgLight, 255, 255, 255);
$goldLight = imagecolorallocate($imgLight, 250, 204, 21);

imagefilledellipse($imgLight, 100, 120, 110, 110, $white);
imagefilledellipse($imgLight, 100, 120, 90, 90, $transparent);
imagefilledellipse($imgLight, 100, 120, 40, 40, $goldLight);
imagefilledellipse($imgLight, 100, 120, 20, 20, $white);

imagestring($imgLight, 5, 180, 70, 'G A L A D Y A   S T U D I O', $white);
imagestring($imgLight, 5, 180, 71, 'G A L A D Y A   S T U D I O', $white);
imagestring($imgLight, 3, 182, 115, 'S E L F - S H O O T  &  P H O T O P R I N T', $goldLight);
imagestring($imgLight, 2, 182, 142, 'P H I L I P P I N E S  *  E S T . 2 0 2 4', $white);

imagepng($imgLight, $dir . '/galadya-logo-light.png');
imagedestroy($imgLight);

// Also create a modern vector SVG logo
$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 100" width="400" height="100">
  <defs>
    <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#F59E0B" />
      <stop offset="100%" stop-color="#D97706" />
    </linearGradient>
  </defs>
  <g transform="translate(10, 10)">
    <circle cx="40" cy="40" r="36" fill="none" stroke="#0F172A" stroke-width="6"/>
    <circle cx="40" cy="40" r="24" fill="none" stroke="url(#goldGrad)" stroke-width="4"/>
    <circle cx="40" cy="40" r="12" fill="#0F172A"/>
    <circle cx="46" cy="34" r="3" fill="#FFFFFF"/>
  </g>
  <text x="100" y="45" font-family="system-ui, -apple-system, sans-serif" font-weight="800" font-size="28" letter-spacing="4" fill="#0F172A">GALADYA</text>
  <text x="102" y="65" font-family="system-ui, -apple-system, sans-serif" font-weight="600" font-size="12" letter-spacing="6" fill="#D97706">STUDIO PHOTOPRINT</text>
  <text x="102" y="80" font-family="system-ui, -apple-system, sans-serif" font-weight="400" font-size="9" letter-spacing="3" fill="#64748B">MANILA, PHILIPPINES</text>
</svg>
SVG;
file_put_contents($dir . '/galadya-logo.svg', $svg);

echo "Logos created successfully!\n";


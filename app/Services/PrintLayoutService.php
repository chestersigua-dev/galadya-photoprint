<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * PrintLayoutService
 * 
 * Compiles high-resolution 4R prints (1200 x 1800 px @ 300 DPI):
 * 1. Output A: Dual-strip photostrip composite (10 selected photos, 2 columns of 5 photos,
 *    Galadya Studio logo centered at the bottom footer of each strip, 10px outer margin).
 * 2. Output B: Solo Favorite 4R Print (single favorite photo scaled & centered,
 *    Galadya Studio logo at bottom-left corner sized at exactly 3% of total canvas area,
 *    10px outer margin).
 */
class PrintLayoutService
{
    protected int $canvasWidth = 1200;
    protected int $canvasHeight = 1800;
    protected int $margin = 10;
    protected int $dpi = 300;
    protected string $logoPath;

    public function __construct()
    {
        $this->canvasWidth = (int) Setting::get('print_canvas_width', 1200);
        $this->canvasHeight = (int) Setting::get('print_canvas_height', 1800);
        $this->margin = (int) Setting::get('print_margin_px', 10);
        $this->dpi = (int) Setting::get('print_dpi', 300);
        $this->logoPath = public_path('images/galadya-logo.png');
    }

    /**
     * Generate Output A: Dual-Strip Photostrip Composite (10 Photos).
     *
     * @param array  $photoPaths Array of absolute paths to selected photos (up to 10)
     * @param string $outputPath Absolute destination path for compiled JPEG
     * @param array  $customMeta Optional metadata (e.g. client name, date, transaction code)
     * @return array Result metadata
     */
    public function generateDualPhotostrip(array $photoPaths, string $outputPath, array $customMeta = []): array
    {
        $w = $this->canvasWidth;
        $h = $this->canvasHeight;
        $m = $this->margin;

        // Ensure target folder exists
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create 1200 x 1800 canvas with white background
        $canvas = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $darkSlate = imagecolorallocate($canvas, 15, 23, 42); // #0F172A
        $gold = imagecolorallocate($canvas, 212, 160, 23); // #D4A017
        $lightGray = imagecolorallocate($canvas, 226, 232, 240); // #E2E8F0
        $guideColor = imagecolorallocate($canvas, 203, 213, 225); // #CBD5E1

        imagefill($canvas, 0, 0, $white);

        // Calculate columns
        // Usable width = 1200 - (10 * 2) = 1180 px
        // Usable height = 1800 - (10 * 2) = 1780 px
        $usableWidth = $w - ($m * 2);
        $usableHeight = $h - ($m * 2);

        $stripGap = 20; // 20px gap between column 1 and column 2
        $stripWidth = (int) floor(($usableWidth - $stripGap) / 2); // 580 px

        // Outer bounds for strips
        $col1Left = $m;
        $col2Left = $m + $stripWidth + $stripGap;

        // Draw subtle dotted cutting guide down the middle
        $middleX = (int) ($w / 2);
        for ($gy = $m; $gy < $h - $m; $gy += 12) {
            imageline($canvas, $middleX, $gy, $middleX, $gy + 6, $guideColor);
        }

        // Each strip has 5 photos and a bottom branding footer
        $footerHeight = 170; // footer for logo and session info
        $stripTopPadding = 15;
        $photoGap = 12; // gap between photos in column

        $availablePhotoHeight = $usableHeight - $stripTopPadding - $footerHeight - ($photoGap * 4);
        $slotHeight = (int) floor($availablePhotoHeight / 5); // ~ 312 px
        $slotWidth = $stripWidth - 24; // 12px margin inside strip = 556 px

        // Partition photos into Col 1 (items 0..4) and Col 2 (items 5..9)
        // If fewer photos provided, pad or cycle them
        $paddedPhotos = $this->prepareTenPhotos($photoPaths);
        $col1Photos = array_slice($paddedPhotos, 0, 5);
        $col2Photos = array_slice($paddedPhotos, 5, 5);

        // Render Column 1
        $this->renderStripColumn(
            $canvas,
            $col1Left,
            $m + $stripTopPadding,
            $stripWidth,
            $slotWidth,
            $slotHeight,
            $photoGap,
            $footerHeight,
            $col1Photos,
            $customMeta
        );

        // Render Column 2
        $this->renderStripColumn(
            $canvas,
            $col2Left,
            $m + $stripTopPadding,
            $stripWidth,
            $slotWidth,
            $slotHeight,
            $photoGap,
            $footerHeight,
            $col2Photos,
            $customMeta
        );

        // Save high-resolution JPEG (quality 96)
        imagejpeg($canvas, $outputPath, 96);
        imagedestroy($canvas);

        return [
            'success' => true,
            'type' => 'dual_photostrip',
            'width' => $w,
            'height' => $h,
            'file_path' => $outputPath,
        ];
    }

    /**
     * Render a single 5-photo photostrip column.
     */
    protected function renderStripColumn(
        $canvas,
        int $colLeft,
        int $startY,
        int $stripWidth,
        int $slotWidth,
        int $slotHeight,
        int $photoGap,
        int $footerHeight,
        array $photos,
        array $meta
    ): void {
        $slotX = $colLeft + (int)(($stripWidth - $slotWidth) / 2);
        $currentY = $startY;

        $borderColor = imagecolorallocate($canvas, 241, 245, 249);
        $shadowColor = imagecolorallocate($canvas, 226, 232, 240);
        $darkSlate = imagecolorallocate($canvas, 15, 23, 42);
        $gold = imagecolorallocate($canvas, 212, 160, 23);
        $gray = imagecolorallocate($canvas, 100, 116, 139);

        // Render 5 photos
        foreach ($photos as $index => $photoPath) {
            $photoImg = $this->loadImageResource($photoPath);

            if ($photoImg) {
                // Crop and scale into slotWidth x slotHeight with center crop
                $srcW = imagesx($photoImg);
                $srcH = imagesy($photoImg);

                $targetAspect = $slotWidth / $slotHeight;
                $srcAspect = $srcW / $srcH;

                if ($srcAspect > $targetAspect) {
                    // Source is wider than slot: crop width
                    $cropH = $srcH;
                    $cropW = (int) round($srcH * $targetAspect);
                    $cropX = (int) round(($srcW - $cropW) / 2);
                    $cropY = 0;
                } else {
                    // Source is taller than slot: crop height
                    $cropW = $srcW;
                    $cropH = (int) round($srcW / $targetAspect);
                    $cropX = 0;
                    $cropY = (int) round(($srcH - $cropH) / 2);
                }

                // Draw subtle card border
                imagefilledrectangle($canvas, $slotX - 1, $currentY - 1, $slotX + $slotWidth + 1, $currentY + $slotHeight + 1, $shadowColor);

                // Copy resampled photo into canvas
                imagecopyresampled(
                    $canvas,
                    $photoImg,
                    $slotX,
                    $currentY,
                    $cropX,
                    $cropY,
                    $slotWidth,
                    $slotHeight,
                    $cropW,
                    $cropH
                );

                imagedestroy($photoImg);
            } else {
                // Placeholder block
                imagefilledrectangle($canvas, $slotX, $currentY, $slotX + $slotWidth, $currentY + $slotHeight, $borderColor);
                imagestring($canvas, 4, $slotX + 180, $currentY + 140, "Galadya Studio Shot #" . ($index + 1), $gray);
            }

            $currentY += $slotHeight + $photoGap;
        }

        // Render Footer Branding centered at bottom of each strip
        $footerY = $currentY + 10;
        $this->renderCenteredLogo($canvas, $colLeft, $footerY, $stripWidth, $footerHeight, $meta);
    }

    /**
     * Render the Galadya Studio logo and date centered at the strip footer.
     */
    protected function renderCenteredLogo($canvas, int $x, int $y, int $width, int $height, array $meta): void
    {
        $darkSlate = imagecolorallocate($canvas, 15, 23, 42);
        $gold = imagecolorallocate($canvas, 212, 160, 23);
        $gray = imagecolorallocate($canvas, 100, 116, 139);

        // Load logo if exists
        if (file_exists($this->logoPath)) {
            $logo = @imagecreatefrompng($this->logoPath);
            if ($logo) {
                $logoOrigW = imagesx($logo);
                $logoOrigH = imagesy($logo);

                // Scale logo to fit nicely in footer (width ~ 320px)
                $targetLogoW = 340;
                $targetLogoH = (int) round($logoOrigH * ($targetLogoW / $logoOrigW));

                $logoX = $x + (int)(($width - $targetLogoW) / 2);
                $logoY = $y + 10;

                imagecopyresampled(
                    $canvas,
                    $logo,
                    $logoX,
                    $logoY,
                    0,
                    0,
                    $targetLogoW,
                    $targetLogoH,
                    $logoOrigW,
                    $logoOrigH
                );
                imagedestroy($logo);

                // Timestamp / Session Code
                $dateText = !empty($meta['date']) ? $meta['date'] : date('F d, Y');
                $codeText = !empty($meta['code']) ? $meta['code'] : 'GALADYA-STUDIO';
                $footerLine = "{$codeText}  *  {$dateText}";

                $textX = $x + (int)(($width - (strlen($footerLine) * 7)) / 2);
                imagestring($canvas, 2, $textX, $logoY + $targetLogoH + 8, $footerLine, $gray);

                return;
            }
        }

        // Text fallback if logo file is unavailable
        $title = "G A L A D Y A   S T U D I O";
        $subtitle = "P H O T O P R I N T";
        $tX = $x + (int)(($width - (strlen($title) * 9)) / 2);
        $sX = $x + (int)(($width - (strlen($subtitle) * 8)) / 2);

        imagestring($canvas, 5, $tX, $y + 20, $title, $darkSlate);
        imagestring($canvas, 4, $sX, $y + 50, $subtitle, $gold);
    }

    /**
     * Generate Output B: Solo Favorite 4R Print.
     *
     * Specs:
     * - 4R canvas: 1200 x 1800 px (or 1800 x 1200 px if horizontal).
     * - 10px safe margin all sides.
     * - Single favorite photo scaled and centered.
     * - Logo placed at bottom-left corner, sized at exactly 3% of total canvas area.
     *
     * @param string $photoPath  Path to the favorite photo
     * @param string $outputPath Path for generated JPEG
     * @param array  $customMeta Optional metadata
     * @return array
     */
    public function generateSoloFavorite4R(string $photoPath, string $outputPath, array $customMeta = []): array
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $photoImg = $this->loadImageResource($photoPath);
        if (!$photoImg) {
            throw new Exception("Unable to load favorite photo resource from {$photoPath}");
        }

        $srcW = imagesx($photoImg);
        $srcH = imagesy($photoImg);

        // Determine orientation
        $isLandscape = $srcW > $srcH;
        $canvasW = $isLandscape ? 1800 : 1200;
        $canvasH = $isLandscape ? 1200 : 1800;
        $m = $this->margin; // 10 px

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $borderCol = imagecolorallocate($canvas, 226, 232, 240);
        imagefill($canvas, 0, 0, $white);

        // Available area inside 10px margin
        $availW = $canvasW - ($m * 2);
        $availH = $canvasH - ($m * 2);

        // Scale and center the favorite photo
        $aspectRatio = $srcW / $srcH;
        $availAspect = $availW / $availH;

        if ($aspectRatio > $availAspect) {
            // Photo is wider: fit to width
            $destW = $availW;
            $destH = (int) round($availW / $aspectRatio);
        } else {
            // Photo is taller: fit to height
            $destH = $availH;
            $destW = (int) round($availH * $aspectRatio);
        }

        $destX = $m + (int) round(($availW - $destW) / 2);
        $destY = $m + (int) round(($availH - $destH) / 2);

        // Draw clean image border and copy photo
        imagecopyresampled(
            $canvas,
            $photoImg,
            $destX,
            $destY,
            0,
            0,
            $destW,
            $destH,
            $srcW,
            $srcH
        );
        imagedestroy($photoImg);

        // Apply Branding Logo:
        // "Place the Galadya Studio logo at the bottom-left corner, sized proportionally at 3% of total print canvas area."
        $totalCanvasArea = $canvasW * $canvasH; // 2,160,000 px^2
        $targetLogoArea = $totalCanvasArea * 0.03; // 64,800 px^2

        if (file_exists($this->logoPath)) {
            $logo = @imagecreatefrompng($this->logoPath);
            if ($logo) {
                $logoW = imagesx($logo);
                $logoH = imagesy($logo);
                $logoAspect = $logoW / $logoH; // e.g. 800/240 = 3.333

                // targetLogoArea = (H * logoAspect) * H = logoAspect * H^2
                // H = sqrt(targetLogoArea / logoAspect)
                $scaledH = (int) round(sqrt($targetLogoArea / $logoAspect));
                $scaledW = (int) round($scaledH * $logoAspect);

                // Position at bottom-left corner, offset by safe margin (10px) + padding (20px)
                $logoX = $m + 25;
                $logoY = $canvasH - $m - $scaledH - 25;

                // Subtle semi-transparent backing pill for contrast on any photo background
                $bgPill = imagecreatetruecolor($scaledW + 30, $scaledH + 16);
                imagesavealpha($bgPill, true);
                $transWhite = imagecolorallocatealpha($bgPill, 255, 255, 255, 30); // 76% opaque white
                imagefill($bgPill, 0, 0, $transWhite);

                imagecopy(
                    $canvas,
                    $bgPill,
                    $logoX - 15,
                    $logoY - 8,
                    0,
                    0,
                    $scaledW + 30,
                    $scaledH + 16
                );
                imagedestroy($bgPill);

                // Copy logo
                imagecopyresampled(
                    $canvas,
                    $logo,
                    $logoX,
                    $logoY,
                    0,
                    0,
                    $scaledW,
                    $scaledH,
                    $logoW,
                    $logoH
                );
                imagedestroy($logo);
            }
        }

        // Save high-resolution JPEG
        imagejpeg($canvas, $outputPath, 98);
        imagedestroy($canvas);

        return [
            'success' => true,
            'type' => 'solo_favorite_4r',
            'width' => $canvasW,
            'height' => $canvasH,
            'file_path' => $outputPath,
        ];
    }

    /**
     * Ensure we have exactly 10 photos, padding or repeating if needed.
     */
    protected function prepareTenPhotos(array $photoPaths): array
    {
        $count = count($photoPaths);
        if ($count === 0) {
            return array_fill(0, 10, null);
        }

        $result = $photoPaths;
        while (count($result) < 10) {
            $result = array_merge($result, $photoPaths);
        }

        return array_slice($result, 0, 10);
    }

    /**
     * Load an image resource from disk safely (JPEG, PNG, WEBP, BMP).
     */
    protected function loadImageResource(?string $path)
    {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_BMP  => @imagecreatefrombmp($path),
            default        => null,
        };
    }
}


<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * RawImageService
 *
 * Handles zero-lag ingestion and embedded JPEG extraction for camera RAW formats
 * (Canon .CR2, .CR3, Nikon .NEF, Sony .ARW, Fujifilm .RAF, Olympus .ORF, Panasonic .RW2, Adobe .DNG)
 * and standard raster formats (.JPG, .JPEG, .PNG, .WEBP).
 *
 * Strategy Hierarchy:
 * 1. Standard images -> native GD / Intervention direct thumbnailing.
 * 2. Camera RAW -> Exiftool embedded high-res preview extraction (-PreviewImage / -JpgFromRaw / -OtherImage).
 * 3. Fallback -> PHP Imagick extension (if available).
 * 4. Fallback -> dcraw / libraw CLI (if installed).
 * 5. Safe Fallback -> Metadata-tagged preview canvas to prevent workflow disruption.
 */
class RawImageService
{
    /**
     * Supported RAW camera file extensions.
     */
    protected array $rawExtensions = [
        'cr2', 'cr3', 'nef', 'arw', 'dng', 'raf', 'orf', 'rw2', 'pef', 'srw',
    ];

    /**
     * Supported standard raster formats.
     */
    protected array $standardExtensions = [
        'jpg', 'jpeg', 'png', 'webp', 'bmp',
    ];

    /**
     * Path or command for exiftool executable.
     */
    protected string $exiftoolBin;

    public function __construct()
    {
        $this->exiftoolBin = $this->resolveExifToolBinary();
    }

    /**
     * Resolve the ExifTool binary location.
     */
    protected function resolveExifToolBinary(): string
    {
        $knownPaths = [
            'C:\\Users\\Chester Sigua\\AppData\\Local\\Programs\\ExifTool\\ExifTool.exe',
            'C:\\Program Files\\ExifTool\\exiftool.exe',
            'C:\\Program Files\\Skylum\\Aperty\\exiftool.exe',
            'exiftool',
        ];

        foreach ($knownPaths as $path) {
            if ($path === 'exiftool') {
                return 'exiftool';
            }
            if (file_exists($path)) {
                return $path;
            }
        }

        return 'exiftool';
    }

    /**
     * Check if a file extension is a camera RAW format.
     */
    public function isRaw(string $extension): bool
    {
        return in_array(strtolower(trim($extension, '.')), $this->rawExtensions, true);
    }

    /**
     * Check if a file extension is a standard image format.
     */
    public function isStandardImage(string $extension): bool
    {
        return in_array(strtolower(trim($extension, '.')), $this->standardExtensions, true);
    }

    /**
     * Extract preview from uploaded file and save to destination.
     *
     * @param  string  $sourceFullPath  Absolute path to source image (RAW or standard)
     * @param  string  $destFullPath  Absolute path where preview JPEG should be written
     * @param  int  $maxDimension  Max width/height for web preview (e.g. 1600px)
     * @return array Metadata about extracted preview
     */
    public function processImage(string $sourceFullPath, string $destFullPath, int $maxDimension = 1600): array
    {
        $extension = strtolower(pathinfo($sourceFullPath, PATHINFO_EXTENSION));
        $destDir = dirname($destFullPath);
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Standard raster images
        if ($this->isStandardImage($extension)) {
            return $this->processStandardImage($sourceFullPath, $destFullPath, $maxDimension);
        }

        // Camera RAW formats
        if ($this->isRaw($extension)) {
            return $this->processRawImage($sourceFullPath, $destFullPath, $maxDimension);
        }

        // Generic fallback for unsupported extensions
        return $this->generatePlaceholderPreview(
            $sourceFullPath,
            $destFullPath,
            "Unsupported format: .{$extension}"
        );
    }

    /**
     * Process standard JPG/PNG into web-optimized preview.
     */
    protected function processStandardImage(string $sourcePath, string $destPath, int $maxDimension): array
    {
        try {
            $info = @getimagesize($sourcePath);
            if (! $info) {
                throw new Exception("Unable to read image dimensions from {$sourcePath}");
            }

            [$srcWidth, $srcHeight, $imageType] = $info;

            $srcImage = match ($imageType) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
                IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
                IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
                IMAGETYPE_BMP => @imagecreatefrombmp($sourcePath),
                default => null,
            };

            if (! $srcImage) {
                // If native GD fails, simply copy
                copy($sourcePath, $destPath);

                return [
                    'success' => true,
                    'method' => 'file_copy',
                    'width' => $srcWidth,
                    'height' => $srcHeight,
                    'is_raw' => false,
                ];
            }

            // Correct EXIF orientation for JPEG
            if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($sourcePath);
                if (! empty($exif['Orientation'])) {
                    $srcImage = $this->fixOrientation($srcImage, $exif['Orientation']);
                    $srcWidth = imagesx($srcImage);
                    $srcHeight = imagesy($srcImage);
                }
            }

            // Downscale if larger than maxDimension
            if ($srcWidth > $maxDimension || $srcHeight > $maxDimension) {
                $ratio = min($maxDimension / $srcWidth, $maxDimension / $srcHeight);
                $newWidth = (int) round($srcWidth * $ratio);
                $newHeight = (int) round($srcHeight * $ratio);

                $target = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($target, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
                imagejpeg($target, $destPath, 90);
                imagedestroy($target);
                imagedestroy($srcImage);

                return [
                    'success' => true,
                    'method' => 'gd_downsampled',
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'is_raw' => false,
                ];
            }

            imagejpeg($srcImage, $destPath, 92);
            imagedestroy($srcImage);

            return [
                'success' => true,
                'method' => 'gd_direct',
                'width' => $srcWidth,
                'height' => $srcHeight,
                'is_raw' => false,
            ];
        } catch (Exception $e) {
            Log::warning("Standard image processing failed: {$e->getMessage()}. Copying directly.");
            copy($sourcePath, $destPath);

            return [
                'success' => true,
                'method' => 'file_copy_fallback',
                'width' => 1200,
                'height' => 1800,
                'is_raw' => false,
            ];
        }
    }

    /**
     * Process Camera RAW format using ExifTool, Imagick, or CLI fallbacks.
     */
    protected function processRawImage(string $sourcePath, string $destPath, int $maxDimension): array
    {
        // Strategy A: ExifTool embedded JPEG extraction (Fastest & Zero Lag)
        $exifResult = $this->extractViaExifTool($sourcePath, $destPath);
        if ($exifResult['success']) {
            return $exifResult;
        }

        // Strategy B: Imagick PHP extension
        if (extension_loaded('imagick')) {
            $imagickResult = $this->extractViaImagick($sourcePath, $destPath, $maxDimension);
            if ($imagickResult['success']) {
                return $imagickResult;
            }
        }

        // Strategy C: dcraw CLI
        $dcrawResult = $this->extractViaDcraw($sourcePath, $destPath);
        if ($dcrawResult['success']) {
            return $dcrawResult;
        }

        // Strategy D: Graceful placeholder preview with RAW banner
        return $this->generatePlaceholderPreview(
            $sourcePath,
            $destPath,
            'RAW File: '.basename($sourcePath)
        );
    }

    /**
     * Extract embedded preview via ExifTool.
     * Checks PreviewImage, then JpgFromRaw, then OtherImage.
     */
    protected function extractViaExifTool(string $sourcePath, string $destPath): array
    {
        $tags = ['PreviewImage', 'JpgFromRaw', 'OtherImage', 'ThumbnailImage'];

        foreach ($tags as $tag) {
            try {
                // Command syntax: exiftool -b -TagName "source" > "destination"
                // Or write directly via stdout stream
                $cmd = sprintf(
                    '"%s" -b -%s "%s"',
                    $this->exiftoolBin,
                    $tag,
                    $sourcePath
                );

                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];

                $process = @proc_open($cmd, $descriptors, $pipes);
                if (is_resource($process)) {
                    fclose($pipes[0]);
                    $stdout = stream_get_contents($pipes[1]);
                    $stderr = stream_get_contents($pipes[2]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    $exitCode = proc_close($process);

                    // Check if stdout contains valid JPEG header (0xFF, 0xD8, 0xFF)
                    if ($exitCode === 0 && strlen($stdout) > 1000 && substr($stdout, 0, 3) === "\xFF\xD8\xFF") {
                        file_put_contents($destPath, $stdout);
                        $dims = @getimagesize($destPath);
                        $w = $dims ? $dims[0] : 1600;
                        $h = $dims ? $dims[1] : 1200;

                        return [
                            'success' => true,
                            'method' => "exiftool_{$tag}",
                            'width' => $w,
                            'height' => $h,
                            'is_raw' => true,
                        ];
                    }
                }
            } catch (Exception $e) {
                Log::debug("ExifTool tag {$tag} extraction attempt failed: {$e->getMessage()}");
            }
        }

        return ['success' => false];
    }

    /**
     * Extract preview using Imagick extension.
     */
    protected function extractViaImagick(string $sourcePath, string $destPath, int $maxDimension): array
    {
        try {
            $imagick = new \Imagick;
            // Read first embedded frame
            $imagick->readImage($sourcePath.'[0]');
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);

            $w = $imagick->getImageWidth();
            $h = $imagick->getImageHeight();

            if ($w > $maxDimension || $h > $maxDimension) {
                $imagick->scaleImage($maxDimension, $maxDimension, true);
            }

            $imagick->writeImage($destPath);
            $imagick->clear();
            $imagick->destroy();

            $dims = @getimagesize($destPath);

            return [
                'success' => true,
                'method' => 'imagick',
                'width' => $dims ? $dims[0] : $w,
                'height' => $dims ? $dims[1] : $h,
                'is_raw' => true,
            ];
        } catch (Exception $e) {
            Log::debug("Imagick RAW conversion failed: {$e->getMessage()}");

            return ['success' => false];
        }
    }

    /**
     * Extract thumbnail via dcraw CLI.
     */
    protected function extractViaDcraw(string $sourcePath, string $destPath): array
    {
        try {
            // dcraw -e extracts thumbnail in place with .thumb.jpg extension
            $cmd = sprintf('dcraw -e "%s"', $sourcePath);
            exec($cmd, $output, $code);

            $thumbPattern = preg_replace('/\.[^.]+$/', '.thumb.jpg', $sourcePath);
            if (file_exists($thumbPattern)) {
                rename($thumbPattern, $destPath);
                $dims = @getimagesize($destPath);

                return [
                    'success' => true,
                    'method' => 'dcraw',
                    'width' => $dims ? $dims[0] : 1200,
                    'height' => $dims ? $dims[1] : 800,
                    'is_raw' => true,
                ];
            }
        } catch (Exception $e) {
            Log::debug("dcraw extraction failed: {$e->getMessage()}");
        }

        return ['success' => false];
    }

    /**
     * Generate an informative placeholder preview so the operator UI never crashes.
     */
    protected function generatePlaceholderPreview(string $sourcePath, string $destPath, string $label): array
    {
        $w = 1200;
        $h = 1600;
        $img = imagecreatetruecolor($w, $h);

        $bg = imagecolorallocate($img, 15, 23, 42); // Slate 900
        $gold = imagecolorallocate($img, 212, 160, 23); // Gold
        $white = imagecolorallocate($img, 248, 250, 252);
        $gray = imagecolorallocate($img, 148, 163, 184);

        imagefill($img, 0, 0, $bg);

        // Draw camera frame
        imagerectangle($img, 40, 40, $w - 40, $h - 40, $gold);
        imagerectangle($img, 42, 42, $w - 42, $h - 42, $gold);

        // Header
        imagestring($img, 5, (int) (($w - 280) / 2), 650, 'G A L A D Y A   S T U D I O', $gold);
        imagestring($img, 5, (int) (($w - strlen($label) * 9) / 2), 750, $label, $white);
        imagestring($img, 4, (int) (($w - 320) / 2), 850, 'Camera RAW Capture Ingested', $gray);
        imagestring($img, 3, (int) (($w - 360) / 2), 920, 'High-Res print pipeline ready', $gray);

        imagejpeg($img, $destPath, 88);
        imagedestroy($img);

        return [
            'success' => true,
            'method' => 'placeholder_canvas',
            'width' => $w,
            'height' => $h,
            'is_raw' => true,
        ];
    }

    /**
     * Fix JPEG EXIF orientation.
     */
    protected function fixOrientation($image, int $orientation)
    {
        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}

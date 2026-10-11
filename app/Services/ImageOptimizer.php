<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageOptimizer
{
    /**
     * Supported raster MIME types that can be compressed.
     */
    protected const RASTER_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
        'image/x-ms-bmp',
    ];

    /**
     * Optimize and store an uploaded file.
     *
     * Raster images (JPEG, PNG, WebP, GIF, BMP) are resized within the maximum dimensions,
     * corrected for EXIF rotation, and re-encoded to WebP format at high quality, reducing file size by 60-80%.
     * Non-image files (such as PDF) or unsupported formats are safely stored directly.
     *
     * @param  UploadedFile  $file  Uploaded file instance
     * @param  string  $directory  Target subfolder on the public disk
     * @param  int  $maxWidth  Maximum width in pixels (default: 1600)
     * @param  int  $maxHeight  Maximum height in pixels (default: 1600)
     * @param  int  $quality  Compression quality (1-100, default: 82)
     * @param  string  $disk  Storage disk name (default: 'public')
     * @return array{path: string, url: string, size: int, mime: string, is_compressed: bool, original_size: int, saved_bytes: int, compression_ratio: float}
     */
    public static function optimizeAndStore(
        UploadedFile $file,
        string $directory = 'uploads',
        int $maxWidth = 1600,
        int $maxHeight = 1600,
        int $quality = 82,
        string $disk = 'public'
    ): array {
        $originalSize = $file->getSize() ?: 0;
        $mime = strtolower((string) ($file->getMimeType() ?: 'application/octet-stream'));

        // If file is not a compressible raster image, store directly
        if (! self::isCompressibleImage($file, $mime) || ! extension_loaded('gd')) {
            $path = $file->store($directory, $disk);

            return [
                'path' => $path,
                'url' => '/storage/'.$path,
                'size' => $originalSize,
                'mime' => $mime,
                'is_compressed' => false,
                'original_size' => $originalSize,
                'saved_bytes' => 0,
                'compression_ratio' => 0.0,
            ];
        }

        try {
            $realPath = $file->getRealPath();
            $fileContent = file_get_contents($realPath);

            if ($fileContent === false) {
                throw new \RuntimeException('Failed to read uploaded file contents.');
            }

            /** @var GdImage|resource|false $sourceImage */
            $sourceImage = @imagecreatefromstring($fileContent);

            if (! $sourceImage) {
                // If imagecreatefromstring failed, fallback to standard store
                throw new \RuntimeException('GD could not decode image resource.');
            }

            // Correct EXIF orientation for JPEG images
            $sourceImage = self::correctExifOrientation($sourceImage, $realPath, $mime);

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);

            // Compute scaling ratio (never upscale)
            $ratio = min($maxWidth / max(1, $origWidth), $maxHeight / max(1, $origHeight), 1.0);
            $targetWidth = max(1, (int) round($origWidth * $ratio));
            $targetHeight = max(1, (int) round($origHeight * $ratio));

            // Create canvas with alpha transparency support
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);

            // Resample with smooth bicubic interpolation
            imagecopyresampled(
                $targetImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $origWidth,
                $origHeight
            );

            // Compress to WebP
            ob_start();
            $encoded = imagewebp($targetImage, null, $quality);
            $compressedContent = ob_get_clean();

            // Cleanup memory
            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            if (! $encoded || $compressedContent === false || strlen($compressedContent) === 0) {
                throw new \RuntimeException('WebP encoding failed.');
            }

            $compressedSize = strlen($compressedContent);

            // Generate clean unique filename
            $filename = Str::random(40).'.webp';
            $storagePath = trim($directory, '/').'/'.$filename;

            Storage::disk($disk)->put($storagePath, $compressedContent);

            $savedBytes = max(0, $originalSize - $compressedSize);
            $ratioPercent = $originalSize > 0
                ? round(($savedBytes / $originalSize) * 100, 1)
                : 0.0;

            return [
                'path' => $storagePath,
                'url' => '/storage/'.$storagePath,
                'size' => $compressedSize,
                'mime' => 'image/webp',
                'is_compressed' => true,
                'original_size' => $originalSize,
                'saved_bytes' => $savedBytes,
                'compression_ratio' => $ratioPercent,
            ];
        } catch (Throwable $e) {
            Log::warning('Image optimization failed, falling back to standard store: '.$e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'mime' => $mime,
            ]);

            $path = $file->store($directory, $disk);

            return [
                'path' => $path,
                'url' => '/storage/'.$path,
                'size' => $originalSize,
                'mime' => $mime,
                'is_compressed' => false,
                'original_size' => $originalSize,
                'saved_bytes' => 0,
                'compression_ratio' => 0.0,
            ];
        }
    }

    /**
     * Check if the uploaded file is a compressible raster image.
     */
    public static function isCompressibleImage(UploadedFile $file, ?string $mime = null): bool
    {
        $mime = $mime ?: strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($mime, self::RASTER_MIMES, true)) {
            return true;
        }

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
    }

    /**
     * Correct orientation for JPEG images containing EXIF Orientation tag.
     *
     * @param  GdImage|resource  $image
     * @return GdImage|resource
     */
    protected static function correctExifOrientation($image, string $path, string $mime)
    {
        if (! function_exists('exif_read_data') || ! in_array($mime, ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        try {
            $exif = @exif_read_data($path);
            $orientation = $exif['Orientation'] ?? null;

            if ($orientation === 3) {
                return imagerotate($image, 180, 0) ?: $image;
            } elseif ($orientation === 6) {
                return imagerotate($image, -90, 0) ?: $image;
            } elseif ($orientation === 8) {
                return imagerotate($image, 90, 0) ?: $image;
            }
        } catch (Throwable) {
            // Ignore exif errors and proceed with original orientation
        }

        return $image;
    }
}

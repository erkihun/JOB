<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Web-sized WebP copies of images uploaded to the "public" disk (hero slides, logo, favicon).
 *
 * - The uploaded original is never modified or deleted: e-mails, backups and the admin
 *   forms keep using it. Only <img> tags on web pages ask for a copy.
 * - Copies are generated once, on first use, into storage/app/public/optimized/ and reused
 *   afterwards. The file name hashes the source path, modification time, size and target
 *   box, so replacing an upload produces a fresh copy automatically.
 * - Every failure path (unsupported type, oversized image, low memory, GD error, a copy that
 *   would be larger than the original) falls back to the original path. A page never breaks
 *   because of this class.
 *
 * Methods return storage *paths* (not URLs) so each caller keeps using the same URL helper
 * it used before (Storage::url() or Storage::disk('public')->url()).
 */
final class OptimizedImage
{
    public const DIRECTORY = 'optimized';

    /** Bump to regenerate every copy (e.g. after changing the encoder settings). */
    private const VERSION = 1;

    private const QUALITY = 78;

    private const DISK = 'public';

    /** Never decode images above this many pixels (GD needs ~5 bytes per pixel). */
    private const MAX_SOURCE_PIXELS = 40_000_000;

    private const SOURCE_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    /** Full-width hero slide candidates (srcset). Phones pick ~640–1024, desktops 1600–1920. */
    public const HERO_WIDTHS = [640, 1024, 1600, 1920];

    /**
     * Organisation logo, as shown in headers and sign-in cards (36–48px tall) and the admin
     * sidebar (Settings › Appearance › logo size, max 72px) at up to 3x pixel density.
     */
    public static function logo(?string $path): ?string
    {
        return self::path($path, 640, 224);
    }

    /** Browser-tab icon: browsers draw it at 16–32px, 96px covers high-density screens. */
    public static function favicon(?string $path): ?string
    {
        return self::path($path, 96, 96);
    }

    /** @return list<array{path: string, width: int, height: int}> */
    public static function hero(?string $path): array
    {
        return self::candidates($path, self::HERO_WIDTHS);
    }

    /**
     * Path of a WebP copy that fits inside $maxWidth × $maxHeight (never upscaled),
     * or the original path when no smaller copy can be made.
     */
    public static function path(?string $path, int $maxWidth, ?int $maxHeight = null): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        return self::variant($path, $maxWidth, $maxHeight)['path'] ?? $path;
    }

    /**
     * Responsive candidates for a full-width image: one copy per width up to the original's
     * width. Returns [['path' => …, 'width' => …, 'height' => …], …] ordered by width, or an
     * empty array when the image can't be optimised (callers then use the original).
     *
     * @param  list<int>  $widths
     * @return list<array{path: string, width: int, height: int}>
     */
    public static function candidates(?string $path, array $widths): array
    {
        if ($path === null || $path === '') {
            return [];
        }

        sort($widths);
        $out = [];
        foreach ($widths as $width) {
            $variant = self::variant($path, $width, null);
            if ($variant === null) {
                return [];
            }
            // Widths past the original's size all yield the same copy; keep one.
            if ($out !== [] && $out[array_key_last($out)]['width'] === $variant['width']) {
                break;
            }
            $out[] = $variant;
        }

        return $out;
    }

    /**
     * Generate (or reuse) the copy. Null when the original should be served instead.
     *
     * @return array{path: string, width: int, height: int}|null
     */
    public static function variant(string $path, int $maxWidth, ?int $maxHeight = null): ?array
    {
        try {
            $disk = Storage::disk(self::DISK);
            if (! $disk->exists($path)) {
                return null;
            }

            $source = $disk->path($path);
            $info = @getimagesize($source);
            if ($info === false || ! in_array($info[2], self::SOURCE_TYPES, true)) {
                return null;
            }

            $originalBytes = (int) filesize($source);
            $key = sha1(implode('|', [self::VERSION, $path, filemtime($source), $originalBytes, $maxWidth, $maxHeight, self::QUALITY]));
            $target = self::DIRECTORY.'/'.$key.'.webp';
            $targetFile = $disk->path($target);

            if (! is_file($targetFile) && ! self::generate($source, $info, $targetFile, $maxWidth, $maxHeight)) {
                return null;
            }

            $size = @getimagesize($targetFile);
            if ($size === false) {
                return null;
            }

            // A copy that is not smaller than the original (already-tiny PNG, etc.) is pointless.
            if ((int) filesize($targetFile) >= $originalBytes && $size[0] >= $info[0]) {
                return null;
            }

            return ['path' => $target, 'width' => $size[0], 'height' => $size[1]];
        } catch (Throwable $e) {
            Log::warning('OptimizedImage: serving original', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Delete copies that no longer belong to any of the given (still-used) copy paths.
     *
     * @param  list<string>  $keep
     */
    public static function prune(array $keep): int
    {
        $disk = Storage::disk(self::DISK);
        $keep = array_flip($keep);
        $deleted = 0;
        foreach ($disk->files(self::DIRECTORY) as $file) {
            if (! isset($keep[$file]) && str_ends_with($file, '.webp')) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /** @param  array<int|string, mixed>  $info  getimagesize() result */
    private static function generate(string $source, array $info, string $targetFile, int $maxWidth, ?int $maxHeight): bool
    {
        [$width, $height, $type] = $info;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_SOURCE_PIXELS || ! self::hasMemoryFor($width, $height)) {
            return false;
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => false,
        };
        if ($image === false) {
            return false;
        }

        if ($type === IMAGETYPE_JPEG) {
            $image = self::applyExifOrientation($image, $source);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxWidth / $width, $maxHeight !== null ? $maxHeight / $height : 1);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        // Always copy onto a true-colour canvas: imagewebp() rejects palette images,
        // and this keeps PNG transparency intact.
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $directory = dirname($targetFile);
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return false;
        }

        // Write to a temp file and rename, so concurrent requests never see a half-written image.
        $temp = $targetFile.'.'.bin2hex(random_bytes(4)).'.tmp';
        $ok = imagewebp($canvas, $temp, self::QUALITY);
        if (! $ok || ! @rename($temp, $targetFile)) {
            @unlink($temp);

            return is_file($targetFile);
        }

        return true;
    }

    /** Phone photos store rotation in EXIF; browsers honour it, GD does not. */
    private static function applyExifOrientation(\GdImage $image, string $source): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($source);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        // imagerotate() turns counter-clockwise.
        $steps = match ($orientation) {
            2 => [['flip', IMG_FLIP_HORIZONTAL]],
            3 => [['rotate', 180]],
            4 => [['flip', IMG_FLIP_VERTICAL]],
            5 => [['flip', IMG_FLIP_HORIZONTAL], ['rotate', 90]],
            6 => [['rotate', -90]],
            7 => [['flip', IMG_FLIP_HORIZONTAL], ['rotate', -90]],
            8 => [['rotate', 90]],
            default => [],
        };

        foreach ($steps as [$op, $arg]) {
            if ($op === 'flip') {
                imageflip($image, $arg);
            } else {
                $rotated = imagerotate($image, $arg, 0);
                if ($rotated !== false) {
                    $image = $rotated;
                }
            }
        }

        return $image;
    }

    private static function hasMemoryFor(int $width, int $height): bool
    {
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true; // unlimited
        }

        // Source bitmap + resized canvas + headroom.
        $needed = (int) ($width * $height * 5 * 1.5);

        return memory_get_usage(true) + $needed < $limit * 0.8;
    }

    private static function bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}

<?php

declare(strict_types=1);

use App\Models\HeroSlider;
use App\Models\Setting;
use App\Support\OptimizedImage;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

/** A noisy photo-like JPEG (noise keeps it from compressing to almost nothing). */
function fakeJpeg(string $path, int $width, int $height, ?int $exifOrientation = null): string
{
    $image = imagecreatetruecolor($width, $height);
    mt_srand(42);
    for ($y = 0; $y < $height; $y += 8) {
        for ($x = 0; $x < $width; $x += 8) {
            imagefilledrectangle($image, $x, $y, $x + 7, $y + 7, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
    }
    ob_start();
    imagejpeg($image, null, 92);
    $bytes = (string) ob_get_clean();

    if ($exifOrientation !== null) {
        // Minimal EXIF APP1 segment: little-endian TIFF, one IFD0 entry (0x0112 Orientation, SHORT).
        $tiff = 'II'.pack('v', 42).pack('V', 8)
            .pack('v', 1).pack('vvV', 0x0112, 3, 1).pack('v', $exifOrientation).pack('v', 0)
            .pack('V', 0);
        $app1 = "Exif\0\0".$tiff;
        $bytes = "\xFF\xD8\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($bytes, 2);
    }

    Storage::disk('public')->put($path, $bytes);

    return $path;
}

function fakePng(string $path, int $width, int $height, bool $palette = false): string
{
    $image = $palette ? imagecreate($width, $height) : imagecreatetruecolor($width, $height);
    if (! $palette) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127)); // transparent
        mt_srand(7);
        for ($i = 0; $i < 400; $i++) {
            $x = mt_rand(10, $width - 20);
            $y = mt_rand(10, $height - 20);
            imagefilledrectangle($image, $x, $y, $x + 8, $y + 8, imagecolorallocatealpha($image, mt_rand(0, 255), mt_rand(0, 255), 200, 0));
        }
    } else {
        imagecolorallocate($image, 255, 255, 255);
        for ($i = 0; $i < 60; $i++) {
            imageline($image, 0, $i * 5, $width, $height - $i * 3, imagecolorallocate($image, $i * 4, 80, 160));
        }
    }
    ob_start();
    imagepng($image);
    Storage::disk('public')->put($path, (string) ob_get_clean());

    return $path;
}

test('large jpeg gets a smaller webp copy and the original is left untouched', function (): void {
    $path = fakeJpeg('hero-sliders/photo.jpg', 2400, 1200);
    $originalBytes = Storage::disk('public')->get($path);

    $variant = OptimizedImage::variant($path, 1024);

    expect($variant)->not->toBeNull()
        ->and($variant['path'])->toStartWith('optimized/')->toEndWith('.webp')
        ->and($variant['width'])->toBe(1024)
        ->and($variant['height'])->toBe(512)
        ->and(getimagesize(Storage::disk('public')->path($variant['path']))['mime'])->toBe('image/webp')
        ->and(Storage::disk('public')->size($variant['path']))->toBeLessThan(strlen($originalBytes))
        ->and(Storage::disk('public')->get($path))->toBe($originalBytes);
});

test('copies are reused on later calls', function (): void {
    $path = fakeJpeg('hero-sliders/photo.jpg', 1600, 800);
    $first = OptimizedImage::variant($path, 640);
    $mtime = filemtime(Storage::disk('public')->path($first['path']));

    expect(OptimizedImage::variant($path, 640))->toBe($first)
        ->and(filemtime(Storage::disk('public')->path($first['path'])))->toBe($mtime)
        ->and(Storage::disk('public')->files('optimized'))->toHaveCount(1);
});

test('replacing the upload produces a fresh copy', function (): void {
    $path = fakeJpeg('org/logo.jpg', 1200, 600);
    $before = OptimizedImage::logo($path);

    fakeJpeg('org/logo.jpg', 900, 900);
    touch(Storage::disk('public')->path($path), time() + 10);
    clearstatcache();

    expect(OptimizedImage::logo($path))->not->toBe($before);
});

test('images are never upscaled and the box keeps the aspect ratio', function (): void {
    $path = fakeJpeg('org/logo.jpg', 1000, 500);

    $logo = OptimizedImage::variant($path, 640, 224);
    $big = OptimizedImage::variant($path, 4000);

    expect([$logo['width'], $logo['height']])->toBe([448, 224])
        ->and([$big['width'], $big['height']])->toBe([1000, 500]);
});

test('png transparency survives', function (): void {
    $path = fakePng('org/logo.png', 800, 400);

    $variant = OptimizedImage::variant($path, 400);
    $copy = imagecreatefromwebp(Storage::disk('public')->path($variant['path']));
    $corner = imagecolorsforindex($copy, imagecolorat($copy, 0, 0));

    expect($corner['alpha'])->toBe(127);
});

test('palette pngs are converted instead of failing', function (): void {
    $path = fakePng('org/palette.png', 1200, 600, palette: true);

    $variant = OptimizedImage::variant($path, 300);

    expect($variant)->not->toBeNull()->and($variant['width'])->toBe(300);
});

test('exif rotation from phone photos is applied', function (int $orientation, array $expected): void {
    $path = fakeJpeg("hero-sliders/phone-{$orientation}.jpg", 1200, 600, $orientation);

    $variant = OptimizedImage::variant($path, 2000);

    expect([$variant['width'], $variant['height']])->toBe($expected);
})->with([
    'upright (1)' => [1, [1200, 600]],
    'upside down (3)' => [3, [1200, 600]],
    'rotated 90° cw (6)' => [6, [600, 1200]],
    'rotated 90° ccw (8)' => [8, [600, 1200]],
]);

test('falls back to the original path when no copy can be made', function (?string $path): void {
    Storage::disk('public')->put('org/notes.txt', 'not an image');

    expect(OptimizedImage::logo($path))->toBe($path)
        ->and(OptimizedImage::hero($path))->toBe([]);
})->with([
    'missing file' => 'org/missing.png',
    'not an image' => 'org/notes.txt',
    'empty' => '',
    'null' => null,
]);

test('hero candidates stop at the original width', function (): void {
    $path = fakeJpeg('hero-sliders/medium.jpg', 1300, 650);

    $widths = array_column(OptimizedImage::hero($path), 'width');

    expect($widths)->toBe([640, 1024, 1300]);
});

test('home page serves the hero as responsive webp', function (): void {
    $path = fakeJpeg('hero-sliders/home.jpg', 2000, 800);
    HeroSlider::query()->create([
        'title' => ['en' => 'Careers'],
        'image_path' => $path,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toMatch('/srcset="[^"]+\.webp 640w, [^"]+\.webp 1024w, [^"]+\.webp 1600w, [^"]+\.webp 1920w"/')
        // 1920×768 copy covering a 640px-tall hero renders at least 640 × 2.5 = 1600px wide.
        ->and($html)->toContain('sizes="max(100vw, 1600px)"')
        ->and($html)->not->toContain('hero-sliders/home.jpg');
});

test('favicon is a single small webp link', function (): void {
    Setting::set('org.favicon', fakePng('org/icon.png', 512, 512));

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect(substr_count($html, 'rel="icon"'))->toBe(1)
        ->and($html)->not->toContain('shortcut icon')
        ->and($html)->toMatch('#<link rel="icon" href="[^"]*/optimized/[0-9a-f]{40}\.webp"\s+type="image/webp"#');
});

test('images:optimize builds copies and prunes stale ones', function (): void {
    Setting::set('org.logo', fakeJpeg('org/logo.jpg', 1200, 600));
    Storage::disk('public')->put('optimized/'.str_repeat('a', 40).'.webp', 'stale');

    $this->artisan('images:optimize')->assertSuccessful();

    $files = Storage::disk('public')->files('optimized');
    expect($files)->toHaveCount(1)
        ->and($files[0])->toBe(OptimizedImage::logo('org/logo.jpg'));
});

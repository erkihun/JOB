<?php

declare(strict_types=1);

use Illuminate\Support\Str;

test('source files do not contain figma references', function (): void {
    $paths = [
        base_path('app'),
        base_path('resources'),
        base_path('routes'),
        base_path('config'),
        base_path('database'),
        base_path('lang'),
        base_path('docs'),
        base_path('README.md'),
        base_path('package.json'),
        base_path('composer.json'),
        base_path('vite.config.js'),
    ];

    $matches = [];

    foreach ($paths as $path) {
        if (! file_exists($path)) {
            continue;
        }

        if (is_file($path)) {
            $contents = file_get_contents($path);

            if ($contents !== false && Str::contains(Str::lower($contents), 'figma')) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            }

            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $realPath = $file->getRealPath();

            if ($realPath === false) {
                continue;
            }

            if (Str::contains($realPath, DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $contents = file_get_contents($realPath);

            if ($contents !== false && Str::contains(Str::lower($contents), 'figma')) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $realPath);
            }
        }
    }

    expect($matches)->toBe([]);
});

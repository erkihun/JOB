<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HeroSlider;
use App\Models\Setting;
use App\Support\OptimizedImage;
use Illuminate\Console\Command;

/**
 * Pre-builds the WebP copies the public pages use (logo, favicon, active hero slides) so the
 * first visitor after an upload or deploy doesn't wait for encoding, and removes copies of
 * images that have since been replaced or deleted. Safe to run any time; originals are
 * never touched. Pages also build missing copies on demand, so running this is optional.
 */
class OptimizeImagesCommand extends Command
{
    protected $signature = 'images:optimize {--no-prune : Keep copies that are no longer used}';

    protected $description = 'Generate web-optimised WebP copies of the logo, favicon and hero slides';

    public function handle(): int
    {
        $keep = [];
        $report = [];

        $sources = [
            'Logo' => [(string) Setting::get('org.logo', ''), fn (string $p) => [OptimizedImage::logo($p)]],
            'Favicon' => [(string) Setting::get('org.favicon', ''), fn (string $p) => [OptimizedImage::favicon($p)]],
        ];
        foreach (HeroSlider::query()->whereNotNull('image_path')->get() as $slide) {
            $sources['Hero slide '.$slide->id] = [(string) $slide->image_path, fn (string $p) => array_column(OptimizedImage::hero($p), 'path')];
        }

        foreach ($sources as $label => [$path, $build]) {
            if ($path === '') {
                continue;
            }
            $copies = array_values(array_filter($build($path), fn (?string $c) => $c !== null && $c !== $path));
            array_push($keep, ...$copies);
            $report[] = [$label, $path, $copies === [] ? 'original kept (cannot be made smaller)' : count($copies).' cop'.(count($copies) === 1 ? 'y' : 'ies')];
        }

        $this->table(['Image', 'Original', 'Result'], $report);

        if (! $this->option('no-prune')) {
            $deleted = OptimizedImage::prune($keep);
            $this->info("Removed {$deleted} unused cop".($deleted === 1 ? 'y' : 'ies').'.');
        }

        return self::SUCCESS;
    }
}

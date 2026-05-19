<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PdfInstallFontsCommand extends Command
{
    protected $signature = 'pdf:install-fonts';

    protected $description = 'Install Ethiopic fonts required for PDF export';

    private array $candidates = [
        'Ebrima' => ['C:\\Windows\\Fonts\\ebrima.ttf',  '/c/Windows/Fonts/ebrima.ttf'],
        'Ebrima_Bold' => ['C:\\Windows\\Fonts\\ebrimabd.ttf', '/c/Windows/Fonts/ebrimabd.ttf'],
    ];

    public function handle(): int
    {
        $dir = storage_path('fonts');

        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            $this->error('Cannot create directory: '.$dir);

            return self::FAILURE;
        }

        $installed = 0;

        foreach ($this->candidates as $name => $paths) {
            $dest = $dir.DIRECTORY_SEPARATOR.$name.'.ttf';
            if (file_exists($dest)) {
                $this->line("  <info>✓</info> $name already installed.");
                $installed++;

                continue;
            }
            foreach ($paths as $src) {
                if (file_exists($src)) {
                    copy($src, $dest);
                    $this->line("  <info>✓</info> Installed $name from $src");
                    $installed++;
                    break;
                }
            }
        }

        if ($installed === 0) {
            $this->warn('No Ethiopic fonts found automatically.');
            $this->line('Please download <comment>Ebrima.ttf</comment> and place it in: <comment>'.$dir.'</comment>');
            $this->line('Download from: <href=https://fonts.google.com/noto/specimen/Noto+Sans+Ethiopic>Noto Sans Ethiopic</href>');
            $this->line('Rename the file to <comment>Ebrima.ttf</comment> and re-run this command.');

            return self::FAILURE;
        }

        $this->info('Font installation complete. Clear DomPDF cache with: php artisan cache:clear');

        return self::SUCCESS;
    }
}

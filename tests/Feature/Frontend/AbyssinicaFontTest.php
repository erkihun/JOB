<?php

declare(strict_types=1);

use App\Exports\ApplicantsReportExport;
use App\Jobs\GenerateReportExportJob;
use Barryvdh\DomPDF\Facade\Pdf;

test('the abyssinica sil font files ship with the app', function (): void {
    foreach (['woff2', 'woff', 'ttf'] as $ext) {
        expect(public_path("fonts/abyssinica/AbyssinicaSIL-Regular.{$ext}"))->toBeFile();
    }
});

test('report pdf export embeds abyssinica sil so amharic never needs a system font', function (): void {
    $job = (new ReflectionClass(GenerateReportExportJob::class))->newInstanceWithoutConstructor();
    $export = Mockery::mock(ApplicantsReportExport::class);
    $export->shouldReceive('collection')->andReturn(collect([['APP-2026-000001', 'አበበ በቀለ', 'Accountant']]));
    $export->shouldReceive('headings')->andReturn(['Reference', 'ስም', 'Position']);

    $html = (new ReflectionMethod($job, 'renderHtml'))->invoke($job, $export);
    $pdf = Pdf::loadHTML($html)->output();

    expect($html)->toContain("font-family: 'Abyssinica SIL'")
        ->and($pdf)->toStartWith('%PDF')
        ->and($pdf)->toContain('Abyssinica');
});

test('error pages render amharic in abyssinica sil', function (): void {
    $this->get('/this-page-does-not-exist-'.uniqid())
        ->assertNotFound()
        ->assertSee('font-family: "Abyssinica SIL"', false)
        ->assertSee('/fonts/abyssinica/AbyssinicaSIL-Regular.woff2', false);
});

test('web pages lead every font stack with abyssinica sil', function (): void {
    $css = file_get_contents(resource_path('css/app.css'));

    foreach (['--font-sans', '--font-serif-am', '--font-public', '--font-mono'] as $token) {
        expect($css)->toMatch("/{$token}:\\s*'Abyssinica SIL',/");
    }
});

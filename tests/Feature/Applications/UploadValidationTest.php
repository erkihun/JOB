<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('file upload under 2mb passes validation', function () {
    $file = UploadedFile::fake()->create('cv.pdf', 1024, 'application/pdf'); // 1MB

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->passes())->toBeTrue();
});

test('file upload over 2mb fails validation', function () {
    $file = UploadedFile::fake()->create('cv.pdf', 2049, 'application/pdf'); // 2.049MB

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('document'))->toBeTrue();
});

test('file exactly 2mb passes validation', function () {
    $file = UploadedFile::fake()->create('cv.pdf', 2048, 'application/pdf'); // exactly 2MB

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->passes())->toBeTrue();
});

test('pdf file type is allowed', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->passes())->toBeTrue();
});

test('jpg file type is allowed', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->passes())->toBeTrue();
});

test('png file type is allowed', function () {
    $file = UploadedFile::fake()->image('photo.png', 100, 100);

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->passes())->toBeTrue();
});

test('executable file type is rejected', function () {
    $file = UploadedFile::fake()->create('virus.exe', 500, 'application/octet-stream');

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->fails())->toBeTrue();
});

test('docx file type is rejected', function () {
    $file = UploadedFile::fake()->create('resume.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->fails())->toBeTrue();
});

test('zip file type is rejected', function () {
    $file = UploadedFile::fake()->create('docs.zip', 500, 'application/zip');

    $validator = Validator::make(
        ['document' => $file],
        ['document' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']]
    );

    expect($validator->fails())->toBeTrue();
});

test('file storage path is private not public', function () {
    // Validate that the upload disk is not public
    $disk = config('filesystems.default');

    // Default disk for private storage should be 'local' not 'public'
    expect($disk)->toBe('local');
});

<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use App\Models\Application;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

test('admin dashboard loads without figma references', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.title'))
        ->assertSee('data-admin-page-frame', false)
        ->assertSee('X-Admin-Navigation', false)
        ->assertDontSee('figma', false);
});

test('applicant cannot access admin panel', function (): void {
    $applicant = User::factory()->asApplicant()->create();

    $response = $this->actingAs($applicant)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('menu permissions are respected in admin navigation', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('menus.screening'))
        ->assertDontSeeText(__('menus.users'));
});

test('language switcher works for admin users', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('lang.switch', 'am'));

    $response->assertRedirect();
    $response->assertSessionHas('locale', 'am');
    expect($admin->fresh()->preferred_locale)->toBe('am');
});

test('amharic admin layout renders localized dashboard labels', function (): void {
    $admin = User::factory()->admin()->create(['preferred_locale' => 'am']);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('locale-am', false)
        ->assertSeeText(__('dashboard.title'))
        ->assertSeeText(__('dashboard.kpi.open_vacancies'));
});

test('sensitive applicant data is hidden from non hr roles', function (): void {
    $applicant = Applicant::factory()->create([
        'full_name' => 'Sensitive Applicant',
        'email' => 'sensitive@example.com',
        'phone' => '0911000000',
    ]);

    Application::factory()->create([
        'applicant_id' => $applicant->id,
    ]);

    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin')
        ->assertOk()
        ->assertSeeText(__('dashboard.restricted'))
        ->assertDontSeeText('Sensitive Applicant')
        ->assertDontSeeText('sensitive@example.com')
        ->assertDontSeeText('0911000000');
});

test('users page requires users view permission', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin/users')
        ->assertForbidden();
});

test('admin users list does not display applicant accounts', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Admin User']);
    $applicant = User::factory()->asApplicant()->create(['name' => 'Applicant User']);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSeeText('Admin User')
        ->assertDontSeeText('Applicant User')
        ->assertDontSeeText('applicant');

    $this->actingAs($admin)
        ->get('/admin/users?role=applicant')
        ->assertOk()
        ->assertDontSeeText($applicant->name);
});

test('roles page requires roles view permission', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin/roles')
        ->assertForbidden();
});

test('vacancies page requires vacancies view permission', function (): void {
    $reportViewer = User::factory()->reportViewer()->create();

    $this->actingAs($reportViewer)
        ->get('/admin/vacancies')
        ->assertForbidden();
});

test('applications page requires applications view permission', function (): void {
    $reportViewer = User::factory()->reportViewer()->create();

    $this->actingAs($reportViewer)
        ->get('/admin/applications')
        ->assertForbidden();
});

test('screening page requires screening view permission', function (): void {
    $reportViewer = User::factory()->reportViewer()->create();

    $this->actingAs($reportViewer)
        ->get('/admin/screening')
        ->assertForbidden();
});

test('passed screening page requires screening view permission', function (): void {
    $reportViewer = User::factory()->reportViewer()->create();

    $this->actingAs($reportViewer)
        ->get('/admin/screening/passed')
        ->assertForbidden();
});

test('failed screening page requires screening view permission', function (): void {
    $reportViewer = User::factory()->reportViewer()->create();

    $this->actingAs($reportViewer)
        ->get('/admin/screening/failed')
        ->assertForbidden();
});

test('reports page requires reports view permission', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin/reports')
        ->assertForbidden();
});

test('settings page requires settings view permission', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin/settings')
        ->assertForbidden();
});

test('audit logs page requires audit view permission', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->get('/admin/audit-logs')
        ->assertForbidden();
});

test('admin route list contains expected admin routes', function (): void {
    Artisan::call('route:list', ['--path' => 'admin']);
    $output = Artisan::output();

    expect($output)->toContain('admin.dashboard')
        ->toContain('admin.users.index')
        ->toContain('admin.vacancies.index')
        ->toContain('admin.screening.index')
        ->toContain('admin.settings.index');
});

test('admin css uses noto serif ethiopic and not abyssinica', function (): void {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('Noto Serif Ethiopic')
        ->not->toContain('Abyssinica');
});

test('authorized admin can preview applicant profile document inline', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $applicant = Applicant::factory()->create();

    $path = 'applicant-documents/'.$applicant->id.'/sample.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');

    $document = ApplicantProfileDocument::create([
        'applicant_id' => $applicant->id,
        'document_type' => 'documents',
        'file_name' => 'sample.pdf',
        'original_name' => 'sample.pdf',
        'file_path' => $path,
        'file_type' => 'application/pdf',
        'file_size' => strlen('fake pdf content'),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.profile-documents.preview', $document))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'inline; filename="sample.pdf"');
});

test('admin applicant detail page renders profile document download link', function (): void {
    $admin = User::factory()->admin()->create();
    $applicant = Applicant::factory()->create();

    $document = ApplicantProfileDocument::create([
        'applicant_id' => $applicant->id,
        'document_type' => 'documents',
        'file_name' => 'profile.pdf',
        'original_name' => 'profile.pdf',
        'file_path' => 'applicant-documents/'.$applicant->id.'/profile.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1024,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applicants.show', $applicant))
        ->assertOk()
        ->assertSee(route('admin.profile-documents.download', $document), false)
        ->assertDontSee('admin.applicant-profile-documents.download', false);
});

test('authorized admin can download applicant profile document', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $applicant = Applicant::factory()->create();

    $path = 'applicant-documents/'.$applicant->id.'/download.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');

    $document = ApplicantProfileDocument::create([
        'applicant_id' => $applicant->id,
        'document_type' => 'documents',
        'file_name' => 'download.pdf',
        'original_name' => 'download.pdf',
        'file_path' => $path,
        'file_type' => 'application/pdf',
        'file_size' => strlen('fake pdf content'),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.profile-documents.download', $document))
        ->assertOk()
        ->assertDownload('download.pdf');
});

test('system favicon can be uploaded from settings and renders in admin layout', function (): void {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->image('favicon.png', 32, 32)->size(10);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'org' => [
                'favicon' => $file,
            ],
        ])
        ->assertRedirect();

    $favicon = Setting::get('org.favicon');

    expect($favicon)->toBeString()->not->toBe('');
    Storage::disk('public')->assertExists($favicon);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee(Storage::url($favicon), false);
});

test('admin logo size setting is saved and rendered in admin layout', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'appearance' => [
                'logo_size' => 56,
            ],
        ])
        ->assertRedirect();

    expect((int) Setting::get('appearance.logo_size'))->toBe(56);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('width: 56px; height: 56px;', false);
});

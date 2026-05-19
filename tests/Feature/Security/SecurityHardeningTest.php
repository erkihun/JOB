<?php

declare(strict_types=1);

use App\Models\HeroSlider;
use App\Models\Setting;
use App\Models\User;
use App\Models\VacancyAnnouncement;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

function adminUser(): User
{
    return User::factory()->admin()->create();
}

function strongAdminEnvironment(?string $password = 'ProductionAdmin@1234'): void
{
    foreach ([
        'ADMIN_NAME' => 'Production Admin',
        'ADMIN_EMAIL' => 'production-admin@example.com',
        'ADMIN_PASSWORD' => $password,
    ] as $key => $value) {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            continue;
        }

        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

test('announcement content is sanitized before storage', function (): void {
    $this->actingAs(adminUser())->post(route('admin.announcements.store'), [
        'subject' => 'Security Notice',
        'content' => '<p onclick="alert(1)">Hello <strong>team</strong></p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><a href="https://example.com" target="_blank">safe</a>',
        'published_at' => now()->toDateTimeString(),
    ])->assertRedirect(route('admin.announcements.index'));

    $content = VacancyAnnouncement::query()->firstOrFail()->content;

    expect($content)
        ->toContain('<p>Hello <strong>team</strong></p>')
        ->toContain('href="https://example.com"')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:');
});

test('safe announcement html tags remain renderable', function (): void {
    $this->actingAs(adminUser())->post(route('admin.announcements.store'), [
        'subject' => 'Safe Notice',
        'content' => '<p>One<br><em>two</em></p><ul><li>Item</li></ul>',
    ])->assertRedirect(route('admin.announcements.index'));

    expect(VacancyAnnouncement::query()->firstOrFail()->content)
        ->toContain('<p>One<br /><em>two</em></p>')
        ->toContain('<ul><li>Item</li></ul>');
});

test('svg logo upload is rejected and jpg logo upload is accepted', function (): void {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.settings.update'), [
        '_method' => 'PUT',
        'org' => [
            'logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg><script>alert(1)</script></svg>'),
        ],
    ])->assertSessionHasErrors('org.logo');

    $this->actingAs($admin)->post(route('admin.settings.update'), [
        '_method' => 'PUT',
        'org' => [
            'logo' => UploadedFile::fake()->image('logo.jpg', 100, 100),
        ],
    ])->assertSessionHasNoErrors();

    expect(Setting::get('org.logo'))->toEndWith('.jpg');
});

test('hero slider svg image upload is rejected', function (): void {
    $this->actingAs(adminUser())->post(route('admin.hero-sliders.store'), [
        'title_en' => 'Careers',
        'button_link' => 'https://example.com/jobs',
        'image' => UploadedFile::fake()->createWithContent('slide.svg', '<svg><script>alert(1)</script></svg>'),
    ])->assertSessionHasErrors('image');
});

test('hero slider accepts http and https links', function (string $url): void {
    $this->actingAs(adminUser())->post(route('admin.hero-sliders.store'), [
        'title_en' => 'Careers',
        'button_link' => $url,
        'image' => UploadedFile::fake()->image('slide.jpg', 100, 100),
    ])->assertSessionHasNoErrors();

    expect(HeroSlider::query()->latest()->firstOrFail()->button_link)->toBe($url);
})->with([
    'https' => 'https://example.com/jobs',
    'http' => 'http://example.com/jobs',
]);

test('hero slider rejects unsafe links', function (string $url): void {
    $this->actingAs(adminUser())->post(route('admin.hero-sliders.store'), [
        'title_en' => 'Careers',
        'button_link' => $url,
    ])->assertSessionHasErrors('button_link');
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:text/html,<script>alert(1)</script>',
    'ftp' => 'ftp://example.com/file',
    'protocol-relative' => '//example.com/jobs',
]);

test('production admin seeder fails safely without explicit credentials', function (): void {
    app()->detectEnvironment(fn () => 'production');
    strongAdminEnvironment(null);

    expect(fn () => app(AdminUserSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Production admin seeding requires valid ADMIN_NAME');
});

test('production admin seeder creates only env configured super admin', function (): void {
    app()->detectEnvironment(fn () => 'production');
    strongAdminEnvironment('ProductionAdmin@1234');

    app(AdminUserSeeder::class)->run();

    $user = User::where('email', 'production-admin@example.com')->firstOrFail();

    expect($user->hasRole('super_admin'))->toBeTrue()
        ->and(Hash::check('ProductionAdmin@1234', $user->password))->toBeTrue()
        ->and(Hash::check('SuperAdmin@123', $user->password))->toBeFalse()
        ->and(User::whereIn('email', ['superadmin@jobs.local', 'admin@jobs.local', 'screening@jobs.local'])->exists())->toBeFalse();
});

test('local admin seeder remains available and super admin has all permissions', function (): void {
    app()->detectEnvironment(fn () => 'testing');

    app(AdminUserSeeder::class)->run();

    $superAdmin = User::where('email', 'superadmin@jobs.local')->firstOrFail();
    $role = Role::findByName('super_admin');

    expect($superAdmin->hasRole('super_admin'))->toBeTrue()
        ->and($role->permissions()->count())->toBe(Permission::count());
});

test('production hardening docs and env example contain required controls', function (): void {
    $env = file_get_contents(base_path('.env.example'));
    $deployment = file_get_contents(base_path('DEPLOYMENT.md'));
    $security = file_get_contents(base_path('SECURITY.md'));

    expect($env)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('SESSION_SECURE_COOKIE=true')
        ->toContain('SESSION_HTTP_ONLY=true')
        ->toContain('SESSION_SAME_SITE=lax')
        ->toContain('LOG_LEVEL=warning')
        ->and($deployment)->toContain('APP_DEBUG=false')
        ->and($deployment)->toContain('SESSION_SECURE_COOKIE=true')
        ->and($security)->toContain('HTTPS is required')
        ->and($security)->toContain('never commit production .env');
});

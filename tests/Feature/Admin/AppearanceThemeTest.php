<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function saveAppearance(User $admin, array $values)
{
    return test()->actingAs($admin)->put(route('admin.settings.update'), [
        '_section' => 'appearance',
        'appearance' => $values,
    ]);
}

test('saved appearance colours are applied on admin, public and applicant pages', function (): void {
    $admin = User::factory()->admin()->create();

    saveAppearance($admin, [
        'primary_color' => '#047857',
        'sidebar_color' => '#064E3B',
        'accent_color' => '#D97706',
        'logo_size' => 40,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Setting::get('appearance.primary_color'))->toBe('#047857');

    $applicant = Applicant::factory()->create();

    foreach ([
        [$admin, route('admin.dashboard')],
        [null, route('home')],
        [$applicant->user, route('applicant.dashboard')],
    ] as [$user, $url]) {
        $request = $user ? $this->actingAs($user) : $this;
        $request->get($url)->assertOk()
            ->assertSee('--color-brand: #047857;', false)
            ->assertSee('--color-navy: #064E3B;', false)
            ->assertSee('--color-accent: #D97706;', false)
            // hard-coded Tailwind blue / orange utilities follow the theme
            ->assertSee('--color-blue-600: var(--color-brand);', false)
            ->assertSee('--color-orange-600: var(--color-accent);', false);
        auth()->logout();
    }
});

test('the admin sidebar uses the sidebar colour with readable text', function (): void {
    $admin = User::factory()->admin()->create();

    saveAppearance($admin, ['sidebar_color' => '#0F172A']);
    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertSee("classList.toggle('sidebar-light', false)", false)
        ->assertSee('var(--color-navy) 38%', false);

    // A light sidebar switches to dark text.
    saveAppearance($admin, ['sidebar_color' => '#F1F5F9']);
    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertSee("classList.toggle('sidebar-light', true)", false);
});

test('invalid colours are rejected and never reach the page', function (): void {
    $admin = User::factory()->admin()->create();

    saveAppearance($admin, ['primary_color' => 'red;}body{display:none'])
        ->assertSessionHasErrors('appearance.primary_color');

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertDontSee('display:none}', false)
        ->assertSee('--color-brand: #1A56DB;', false);
});

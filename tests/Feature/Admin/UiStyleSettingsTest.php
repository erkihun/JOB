<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use App\Support\UiStyle;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('pages use the classic style until one is chosen', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("document.documentElement.dataset.uiStyle = 'classic'", false);
});

test('settings page offers every interface style', function (): void {
    $html = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee(__('settings.ui_style'))
        ->getContent();

    foreach (UiStyle::STYLES as $style) {
        expect($html)->toContain('name="appearance[ui_style]" value="'.$style.'"')
            ->and($html)->toContain(e(__('settings.ui_style_'.$style)));
    }
});

test('an admin can switch the interface style and every area uses it', function (string $style): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), ['_section' => 'appearance', 'appearance' => ['ui_style' => $style]])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(UiStyle::current())->toBe($style);

    $marker = "document.documentElement.dataset.uiStyle = '{$style}'";
    $this->get(route('home'))->assertSee($marker, false);
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee($marker, false);
    $this->actingAs(User::factory()->asApplicant()->create())->get(route('applicant.dashboard'))->assertSee($marker, false);
})->with(UiStyle::STYLES);

test('unknown interface styles are rejected', function (string $value): void {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update'), ['_section' => 'appearance', 'appearance' => ['ui_style' => $value]])
        ->assertSessionHasErrors('appearance.ui_style');

    expect(UiStyle::current())->toBe(UiStyle::DEFAULT);
})->with([
    'unknown name' => 'neon',
    'script injection' => '"; alert(1); "',
    'css injection' => 'soft"] body{display:none',
]);

test('a bad stored value falls back to classic instead of reaching the page', function (): void {
    Setting::set(UiStyle::SETTING, '</script><script>alert(1)</script>', 'string', 'appearance');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee("dataset.uiStyle = 'classic'", false)
        ->assertDontSee('alert(1)', false);
});

test('saving colours alone keeps the chosen style', function (): void {
    $admin = User::factory()->admin()->create();
    Setting::set(UiStyle::SETTING, 'bento', 'string', 'appearance');

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), ['_section' => 'appearance', 'appearance' => ['primary_color' => '#047857']])
        ->assertSessionHasNoErrors();

    expect(UiStyle::current())->toBe('bento');
});

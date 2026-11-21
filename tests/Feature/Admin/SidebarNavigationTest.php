<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin sidebar groups pages into dropdown categories', function (): void {
    $html = $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.dashboard'))->assertOk()->getContent();

    foreach (['recruitment', 'screening', 'exams', 'access', 'system'] as $group) {
        expect($html)->toContain('aria-controls="nav-group-'.$group.'"')
            ->and($html)->toContain('id="nav-group-'.$group.'"');
    }
    // Every page is still linked, inside its category.
    expect($html)->toContain('href="'.route('admin.vacancies.index').'"')
        ->and($html)->toContain('href="'.route('admin.audit-logs.index').'"');
});

test('the category holding the current page starts open, others start closed', function (): void {
    $html = $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.vacancies.index'))->assertOk()->getContent();

    expect($html)->toContain("adminNavGroup('recruitment', true)")
        ->and($html)->toContain("adminNavGroup('system', false)")
        ->and($html)->toMatch('/id="nav-group-recruitment"[^>]*>/')
        ->and($html)->not->toMatch('/id="nav-group-recruitment"[^>]*style="display:none"/')
        ->and($html)->toMatch('/id="nav-group-system"[^>]*style="display:none"/');

    // The current page's menu link is marked for assistive technology (the breadcrumb marks it too).
    expect($html)->toMatch('#href="'.preg_quote(route('admin.vacancies.index'), '#').'"\s+aria-current="page"#');
});

test('categories only appear for pages the user may open', function (): void {
    $html = $this->actingAs(User::factory()->screeningOfficer()->create())
        ->get(route('admin.screening.index'))->assertOk()->getContent();

    expect($html)->toContain('nav-group-screening')
        ->and($html)->not->toContain('nav-group-system')
        ->and($html)->not->toContain('nav-group-access');
});

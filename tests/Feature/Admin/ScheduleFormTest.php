<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    // Exams can only be scheduled after the recruitment's application period closes.
    $this->vacancy = Vacancy::factory()->pastDeadline()->create();
});

function schedulePayload(array $overrides = []): array
{
    return array_merge([
        'vacancy_id' => test()->vacancy->id,
        'title' => 'Written exam',
        'type' => 'exam',
        'date' => now()->addDays(3)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '11:00',
        'venue' => 'Hall 2',
    ], $overrides);
}

test('create page shows a create button and the invitation preview', function (): void {
    $this->actingAs($this->admin)->get(route('admin.schedules.create'))
        ->assertOk()
        ->assertSee(__('messages.invitation_preview'))
        ->assertSee(__('messages.add_schedule'))
        ->assertDontSee(__('messages.save_changes'));
});

test('valid schedule is created', function (): void {
    $this->actingAs($this->admin)->post(route('admin.schedules.store'), schedulePayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.schedules.index'));

    $this->assertDatabaseHas('exam_interview_schedules', ['title' => 'Written exam', 'venue' => 'Hall 2']);
});

test('invalid schedule input is rejected instead of erroring', function (array $overrides, string $field): void {
    $this->actingAs($this->admin)->post(route('admin.schedules.store'), schedulePayload($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'unknown type' => [['type' => 'party'], 'type'],
    'end before start' => [['start_time' => '14:00', 'end_time' => '10:00'], 'end_time'],
    'badly formatted time' => [['start_time' => '9am'], 'start_time'],
    'date in the past' => [['date' => now()->subDay()->toDateString()], 'date'],
]);

test('the Amharic date picker factory is defined inside the swapped page frame', function (): void {
    // Admin fast navigation replaces only [data-admin-page-frame] and re-runs the
    // scripts inside it; a factory outside the frame would be missing after
    // navigation and leave the calendar empty.
    $this->admin->update(['preferred_locale' => 'am']);
    $html = $this->actingAs($this->admin)->withSession(['locale' => 'am'])
        ->get(route('admin.schedules.create'))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $frameScripts = collect((new DOMXPath($dom))->query('//*[@data-admin-page-frame]//script'))
        ->map(fn (DOMNode $node) => $node->textContent)->implode("\n");

    expect($html)->toContain("ethiopianDatepicker('date',")
        ->and($frameScripts)->toContain('function ethiopianDatepicker(');
});

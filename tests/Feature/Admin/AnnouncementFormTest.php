<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

function announcementPayload(array $overrides = []): array
{
    return $overrides + [
        'subject' => 'Recruitment of ICT professionals',
        'code' => 'ANN-'.fake()->unique()->numerify('####'),
        'opening_date' => now()->toDateString(),
        'closing_date' => now()->addDays(20)->toDateString(),
        'content' => '<p>Apply now.</p>',
    ];
}

test('create page shows publishing choices, application period and institution checkboxes', function (): void {
    Institution::factory()->create(['name' => 'Ministry of Education', 'status' => 'active']);

    $this->actingAs($this->admin)->get(route('admin.announcements.create'))
        ->assertOk()
        ->assertSee(__('messages.ann_publishing'))
        ->assertSee('name="_publish_mode"', false)
        ->assertSee(__('messages.ann_period'))
        ->assertSee('type="checkbox" name="institution_ids[]"', false)
        ->assertSee('Ministry of Education');
});

test('draft, publish now and schedule set the publish date correctly', function (): void {
    $this->actingAs($this->admin)->post(route('admin.announcements.store'),
        announcementPayload(['code' => 'ANN-DRAFT', 'status' => 'draft', '_publish_mode' => 'draft', 'published_at' => now()->addDay()->format('Y-m-d\TH:i')]))
        ->assertSessionHasNoErrors();
    expect(RecruitmentAnnouncement::where('code', 'ANN-DRAFT')->first()->published_at)->toBeNull();

    $this->actingAs($this->admin)->post(route('admin.announcements.store'),
        announcementPayload(['code' => 'ANN-NOW', 'status' => 'published', '_publish_mode' => 'now']))
        ->assertSessionHasNoErrors();
    expect(RecruitmentAnnouncement::where('code', 'ANN-NOW')->first()->isPublished())->toBeTrue();

    $when = now()->addDays(3)->setTime(9, 0);
    $this->actingAs($this->admin)->post(route('admin.announcements.store'),
        announcementPayload(['code' => 'ANN-LATER', 'status' => 'published', '_publish_mode' => 'schedule', 'published_at' => $when->format('Y-m-d\TH:i')]))
        ->assertSessionHasNoErrors();
    $scheduled = RecruitmentAnnouncement::where('code', 'ANN-LATER')->first();
    expect($scheduled->published_at->equalTo($when))->toBeTrue()
        ->and($scheduled->isPublished())->toBeFalse();
});

test('a schedule must be in the future', function (): void {
    $this->actingAs($this->admin)->post(route('admin.announcements.store'),
        announcementPayload(['status' => 'published', '_publish_mode' => 'schedule', 'published_at' => now()->subDay()->format('Y-m-d\TH:i')]))
        ->assertSessionHasErrors('published_at');

    $this->actingAs($this->admin)->post(route('admin.announcements.store'),
        announcementPayload(['status' => 'published', '_publish_mode' => 'schedule']))
        ->assertSessionHasErrors('published_at');
});

test('choosing publish now on a scheduled announcement publishes it immediately', function (): void {
    $announcement = RecruitmentAnnouncement::factory()->create([
        'status' => 'published', 'published_at' => now()->addWeek(),
        'opening_date' => now()->toDateString(), 'closing_date' => now()->addDays(20)->toDateString(),
    ]);

    $this->actingAs($this->admin)->put(route('admin.announcements.update', $announcement),
        announcementPayload(['code' => $announcement->code, 'status' => 'published', '_publish_mode' => 'now']))
        ->assertSessionHasNoErrors();

    expect($announcement->refresh()->isPublished())->toBeTrue();
});

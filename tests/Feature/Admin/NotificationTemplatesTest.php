<?php

declare(strict_types=1);

use App\Actions\Notifications\RenderNotificationTemplateAction;
use App\Enums\NotificationType;
use App\Models\AuditLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

test('index lists every notification type in each language, even with no templates stored', function (): void {
    $response = $this->actingAs($this->admin)->get(route('admin.notification-templates.index'))->assertOk();

    foreach (NotificationType::cases() as $type) {
        $response->assertSee($type->getLabel())
            ->assertSee(route('admin.notification-templates.edit', [$type->value, 'en']), false)
            ->assertSee(route('admin.notification-templates.edit', [$type->value, 'am']), false);
    }
    $response->assertSee(__('messages.ntpl_state_default'));
});

test('editing an uncustomised type starts from the default text and saving creates it', function (): void {
    $this->actingAs($this->admin)->get(route('admin.notification-templates.edit', ['exam_invitation', 'en']))
        ->assertOk()
        ->assertSee(__('messages.ntpl_banner_default'))
        ->assertSee('venue', false);   // placeholder chip available for invitations

    $this->actingAs($this->admin)->put(route('admin.notification-templates.update', ['exam_invitation', 'en']), [
        'subject' => 'Exam for {{ vacancy_title }}',
        'body' => 'Hello {{ applicant_name }}, see you at {{ venue }}.',
        'active' => '1',
    ])->assertRedirect(route('admin.notification-templates.edit', ['exam_invitation', 'en']));

    $template = NotificationTemplate::where('type', 'exam_invitation')->where('locale', 'en')->first();
    expect($template)->not->toBeNull()->and($template->active)->toBeTrue()
        ->and(AuditLog::where('action', 'notification_template_created')->exists())->toBeTrue();

    $rendered = app(RenderNotificationTemplateAction::class)
        ->handle(NotificationType::ExamInvitation, ['applicant_name' => 'Abebe', 'vacancy_title' => 'Clerk', 'venue' => 'Hall A'], 'en');
    expect($rendered['subject'])->toBe('Exam for Clerk')
        ->and($rendered['body'])->toBe('Hello Abebe, see you at Hall A.');
});

test('reset deletes the custom template so the default text is sent again', function (): void {
    NotificationTemplate::create(['type' => 'screening_passed', 'locale' => 'am', 'subject' => 'Custom', 'body' => 'Custom body', 'active' => true]);

    $this->actingAs($this->admin)->delete(route('admin.notification-templates.destroy', ['screening_passed', 'am']))
        ->assertRedirect(route('admin.notification-templates.edit', ['screening_passed', 'am']));

    expect(NotificationTemplate::count())->toBe(0)
        ->and(AuditLog::where('action', 'notification_template_reset')->exists())->toBeTrue();
});

test('unknown types or languages 404 and the page needs the manage permission', function (): void {
    $this->actingAs($this->admin)->get('/admin/notification-templates/not_a_type/en')->assertNotFound();
    $this->actingAs($this->admin)->get('/admin/notification-templates/general/fr')->assertNotFound();

    $officer = User::factory()->screeningOfficer()->create();
    $this->actingAs($officer)->get(route('admin.notification-templates.index'))->assertForbidden();
});

test('each type only offers the placeholders it actually receives', function (): void {
    expect(NotificationType::ExamInvitation->placeholders())->toContain('venue', 'date')
        ->and(NotificationType::ScreeningPassed->placeholders())->not->toContain('venue')
        ->and(NotificationType::Selected->placeholders())->toContain('message');
});

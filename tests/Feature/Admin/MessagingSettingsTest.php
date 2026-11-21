<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\ApplicantNotification;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::preventStrayRequests();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create(['phone' => '+251911123456']);
    $this->actingAs($this->admin);
    $this->saveUrl = route('admin.settings.update');
    $this->sms = [
        'enabled' => '1', 'iam_url' => 'https://iam.region.ethiotelecom.et',
        'smn_url' => 'https://smn.region.ethiotelecom.et', 'username' => 'operator',
        'domain' => 'tenant', 'project_id' => str_repeat('a', 32),
    ];
});

test('telegram uses the saved chat and preserves or removes its encrypted token', function (): void {
    $token = '12345:synthetic-bot-token';
    $payload = ['_section' => 'telegram', 'telegram' => ['enabled' => '1', 'chat_id' => '-100123456']];
    $this->put($this->saveUrl, $payload + ['messaging_secret' => $token])->assertSessionHasNoErrors();
    expect(Crypt::decryptString(Setting::get('telegram.token')))->toBe($token)
        ->and(AuditLog::all()->toJson())->not->toContain($token);
    $this->get(route('admin.settings.index'))->assertOk()->assertSee('test-telegram')->assertSee('test-sms')->assertDontSee($token);
    $this->put($this->saveUrl, $payload + ['messaging_secret' => ''])->assertSessionHasNoErrors();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 321]])]);
    $testUrl = route('admin.settings.messaging.test', 'telegram');
    $this->post($testUrl, ['chat_id' => 'another'])->assertSessionHas('success');
    Http::assertSent(fn (Request $request) => $request->url() === "https://api.telegram.org/bot{$token}/sendMessage"
        && $request['chat_id'] === '-100123456' && filled($request['text']));

    $payload['telegram']['enabled'] = '0';
    $this->put($this->saveUrl, $payload + ['clear_messaging_secret' => '1'])->assertSessionHasNoErrors();
    expect(Setting::get('telegram.token'))->toBe('');
    $this->post($testUrl)->assertSessionHasErrors('telegram');
    Http::assertSentCount(1);
});

test('ethio telecom authenticates the project and submits test and applicant SMS', function (): void {
    $this->put($this->saveUrl, ['_section' => 'sms', 'sms' => $this->sms, 'messaging_secret' => 'iam-secret'])
        ->assertSessionHasNoErrors();
    Http::fake([
        'iam.region.ethiotelecom.et/*' => Http::response([], 201, ['X-Subject-Token' => 'scoped-token']),
        'smn.region.ethiotelecom.et/*' => Http::response(['message_id' => 'accepted']),
    ]);
    $this->post(route('admin.settings.messaging.test', 'sms'), ['phone' => '+251900000000'])->assertSessionHas('success');
    Http::assertSent(fn (Request $r) => $r->url() === $this->sms['iam_url'].'/v3/auth/tokens'
        && $r['auth']['identity']['password']['user']['password'] === 'iam-secret'
        && $r['auth']['scope']['project']['id'] === $this->sms['project_id']);
    Http::assertSent(fn (Request $r) => $r->url() === $this->sms['smn_url'].'/v2/'.$this->sms['project_id'].'/notifications/sms'
        && $r->hasHeader('X-Auth-Token', 'scoped-token') && $r['endpoint'] === $this->admin->phone);

    $applicant = Applicant::factory()->create(['phone' => '+251911222333']);
    $application = Application::factory()->create(['applicant_id' => $applicant->id, 'status' => ApplicationStatus::Selected]);
    $this->post(route('admin.final-results.announce'), [
        'channel' => 'sms', 'application_ids' => [$application->id], 'message' => 'Recruitment update',
    ])->assertSessionHasNoErrors();
    $record = ApplicantNotification::where('application_id', $application->id)->sole();
    expect($record->status)->toBe('sent');
    Http::assertSent(fn (Request $r) => ($r['endpoint'] ?? null) === $applicant->phone && $r['message'] === $record->message);
    Http::assertSentCount(3);
});

test('invalid or unauthorized changes cannot enable messaging or leak submitted secrets', function (): void {
    $this->sms['iam_url'] = 'https://invalid.example';
    $this->put($this->saveUrl, ['_section' => 'sms', 'sms' => $this->sms, 'messaging_secret' => 'private'])
        ->assertSessionHasErrors('sms.iam_url')->assertSessionMissing('_old_input.messaging_secret');
    $this->put($this->saveUrl, ['_section' => 'telegram', 'telegram' => ['enabled' => '1', 'chat_id' => '123']])
        ->assertSessionHasErrors('messaging_secret');
    expect(Setting::get('sms.enabled'))->toBeNull();
    $this->actingAs(User::factory()->screeningOfficer()->create());
    $this->post(route('admin.settings.messaging.test', 'telegram'))->assertForbidden();
    $this->put($this->saveUrl, ['_section' => 'telegram'])->assertForbidden();
    Http::assertNothingSent();
});

test('provider rejection shows a safe error and skips SMS when IAM fails', function (): void {
    $this->put($this->saveUrl, ['_section' => 'sms', 'sms' => $this->sms, 'messaging_secret' => 'private'])
        ->assertSessionHasNoErrors();
    Http::fake(['iam.region.ethiotelecom.et/*' => Http::response(['error' => 'sensitive details'], 403)]);
    $this->post(route('admin.settings.messaging.test', 'sms'))->assertSessionHasErrors(['sms' => __('settings.messaging_failed')]);
    Http::assertSentCount(1);
});

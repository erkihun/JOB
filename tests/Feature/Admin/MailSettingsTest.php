<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Jobs\SendApplicantNotificationJob;
use App\Models\Applicant;
use App\Models\ApplicantNotification;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->profile = [
        'mode' => 'smtp', 'host' => '127.0.0.1', 'port' => 2525, 'security' => 'starttls',
        'username' => 'sender', 'from_address' => 'jobs@example.test', 'from_name' => 'Jobs',
    ];
});

test('saving smtp builds a transport and keeps credentials out of the page and audit log', function (): void {
    $password = '  test-only smtp secret  ';
    $url = route('admin.settings.update');
    $this->actingAs($this->admin)->put($url, ['mail' => $this->profile, 'smtp_password' => $password])
        ->assertSessionHasNoErrors();

    $encrypted = Setting::get('mail.smtp_password');
    $transport = Mail::mailer()->getSymfonyTransport();
    expect(Crypt::decryptString($encrypted))->toBe($password)
        ->and($encrypted)->not->toContain(trim($password))
        ->and($transport->getPassword())->toBe($password)
        ->and($transport->getUsername())->toBe('sender')
        ->and($transport->isTlsRequired())->toBeTrue()
        ->and($transport->getStream()->getPort())->toBe(2525)
        ->and(AuditLog::query()->get()->toJson())->not->toContain(trim($password));
    $this->get(route('admin.settings.index'))->assertOk()->assertDontSee(trim($password))->assertDontSee($encrypted);

    $this->put($url, ['mail' => $this->profile, 'smtp_password' => ''])->assertSessionHasNoErrors();
    expect(Setting::get('mail.smtp_password'))->toBe($encrypted);
    $this->put($url, ['mail' => $this->profile, 'clear_smtp_password' => '1'])->assertSessionHasNoErrors();
    expect(Mail::mailer()->getSymfonyTransport()->getPassword())->toBe('');
    $this->put($url, ['mail' => ['mode' => 'server']])->assertSessionHasNoErrors();
    expect(Mail::getDefaultDriver())->toBe('array');
});

test('old seeded example sender yields to the server sender until explicitly configured', function (): void {
    config(['mail.from.address' => 'verified@example.test']);
    Setting::set('mail.from_address', 'noreply@example.com');
    Mail::mailer();
    expect(config('mail.from.address'))->toBe('verified@example.test');
    $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertSee('verified@example.test');
    $this->put(route('admin.settings.update'), ['mail' => ['from_address' => 'custom@example.test', 'mode' => 'server']])
        ->assertSessionHasNoErrors();
    expect(config('mail.from.address'))->toBe('custom@example.test');
});

test('invalid smtp input never flashes the password or changes delivery', function (): void {
    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'mail' => ['mode' => 'smtp', 'port' => 99999, 'security' => 'invalid'],
        'smtp_password' => 'private-test-value',
    ])->assertSessionHasErrors(['mail.host', 'mail.port', 'mail.security'])
        ->assertSessionMissing('_old_input.smtp_password');
    expect(Setting::get('mail.mode'))->toBeNull();
});

test('test delivery uses the saved sender and the administrators own address', function (): void {
    $this->actingAs($this->admin)->put(route('admin.settings.update'), ['mail' => $this->profile])
        ->assertSessionHasNoErrors();
    $capture = new ArrayTransport;
    Mail::extend('managed_smtp', fn () => $capture);
    $this->post(route('admin.settings.mail.test'), ['email' => 'another@example.test'])
        ->assertSessionHas('success');

    $message = $capture->messages()->sole()->getOriginalMessage();
    expect($message->getTo()[0]->getAddress())->toBe($this->admin->email)
        ->and($message->getFrom()[0]->getAddress())->toBe('jobs@example.test');
});

test('local mode and connection failures give distinct test results', function (): void {
    $this->actingAs($this->admin)->post(route('admin.settings.mail.test'))->assertSessionHas('warning');
    $this->profile['port'] = 1;
    $this->put(route('admin.settings.update'), ['mail' => $this->profile])->assertSessionHasNoErrors();
    $this->post(route('admin.settings.mail.test'))->assertSessionHasErrors('mail')->assertSessionMissing('success');
});

test('staff without settings permissions cannot test or change mail delivery', function (): void {
    $this->actingAs(User::factory()->screeningOfficer()->create());
    $this->post(route('admin.settings.mail.test'))->assertForbidden();
    $this->put(route('admin.settings.update'), ['mail' => $this->profile])->assertForbidden();
});

test('queued email retries use updated settings and preserve a single inbox notification', function (): void {
    $this->profile['port'] = 1;
    $this->actingAs($this->admin)->put(route('admin.settings.update'), ['mail' => $this->profile])
        ->assertSessionHasNoErrors();
    $recipient = User::factory()->asApplicant()->create();
    $record = ApplicantNotification::create([
        'applicant_id' => Applicant::factory()->create(['user_id' => $recipient->id])->id,
        'type' => NotificationType::General, 'channel' => 'email', 'message' => 'Interview update', 'status' => 'pending',
    ]);
    expect(fn () => SendApplicantNotificationJob::dispatchSync($record))->toThrow(TransportException::class);
    expect($record->refresh()->status)->toBe('failed')->and($record->sent_at)->toBeNull();

    // Simulate another process saving settings after this worker resolved a mailer.
    Setting::set('mail.mode', 'server');
    SendApplicantNotificationJob::dispatchSync($record);
    expect($record->refresh()->status)->toBe('sent')
        ->and($recipient->notifications()->count())->toBe(1)
        ->and(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(1);
});

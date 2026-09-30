<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Notifications\RenderNotificationTemplateAction;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every notification type × language is listed. A type without a stored template
 * (or with an inactive one) sends the built-in default text; admins can customise
 * it from that default and reset it again at any time.
 */
class NotificationTemplateController extends Controller
{
    public function __construct(
        private readonly RenderNotificationTemplateAction $renderer,
        private readonly LogAuditAction $audit,
    ) {}

    public function index(): View
    {
        $locales = $this->locales();
        $templates = NotificationTemplate::all()->keyBy(fn ($t) => $t->type->value.'|'.$t->locale);

        $rows = collect(NotificationType::cases())->map(fn (NotificationType $type) => [
            'type' => $type,
            'locales' => collect($locales)->mapWithKeys(fn ($locale) => [$locale => $templates[$type->value.'|'.$locale] ?? null]),
        ]);

        $customised = $templates->filter(fn ($t) => $t->active && in_array($t->locale, $locales, true))->count();
        $totalSlots = count(NotificationType::cases()) * count($locales);

        return view('admin.notification-templates.index', [
            'groups' => $rows->groupBy(fn ($row) => $row['type']->stage()),
            'locales' => $locales,
            'customised' => $customised,
            'totalSlots' => $totalSlots,
        ]);
    }

    public function edit(string $type, string $locale): View
    {
        [$type, $locale] = $this->resolve($type, $locale);
        $template = NotificationTemplate::where('type', $type->value)->where('locale', $locale)->first();
        $default = $this->renderer->defaultTemplate($type, $locale);

        return view('admin.notification-templates.edit', [
            'type' => $type,
            'locale' => $locale,
            'locales' => $this->locales(),
            'template' => $template,
            'default' => $default,
            'subject' => $template?->subject ?? $default['subject'],
            'body' => $template?->body ?? $default['body'],
            'active' => $template?->active ?? true,
            'placeholders' => $type->placeholders(),
        ]);
    }

    public function update(Request $request, string $type, string $locale): RedirectResponse
    {
        [$type, $locale] = $this->resolve($type, $locale);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'active' => ['boolean'],
        ]);

        $template = NotificationTemplate::firstOrNew(['type' => $type->value, 'locale' => $locale]);
        $old = $template->exists ? $template->only(['subject', 'body', 'active']) : null;

        $template->fill([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'active' => $request->boolean('active'),
        ])->save();

        $this->audit->handle(
            action: $old === null ? 'notification_template_created' : 'notification_template_updated',
            module: 'notifications',
            recordId: $template->id,
            oldValues: $old,
            newValues: ['type' => $type->value, 'locale' => $locale, 'active' => $template->active],
        );

        return redirect()->route('admin.notification-templates.edit', [$type->value, $locale])
            ->with('success', __('messages.template_updated'));
    }

    public function destroy(string $type, string $locale): RedirectResponse
    {
        [$type, $locale] = $this->resolve($type, $locale);

        $template = NotificationTemplate::where('type', $type->value)->where('locale', $locale)->first();
        if ($template !== null) {
            $this->audit->handle(
                action: 'notification_template_reset',
                module: 'notifications',
                recordId: $template->id,
                oldValues: $template->only(['subject', 'body', 'active']),
                newValues: ['type' => $type->value, 'locale' => $locale],
            );
            $template->delete();
        }

        return redirect()->route('admin.notification-templates.edit', [$type->value, $locale])
            ->with('success', __('messages.template_reset'));
    }

    /** @return array{0: NotificationType, 1: string} */
    private function resolve(string $type, string $locale): array
    {
        $resolved = NotificationType::tryFrom($type);
        abort_if($resolved === null || ! in_array($locale, $this->locales(), true), 404);

        return [$resolved, $locale];
    }

    /** @return list<string> */
    private function locales(): array
    {
        $available = array_values(array_intersect(['en', 'am'], (array) Setting::get('app.available_locales', ['en', 'am'])));

        return $available !== [] ? $available : ['en'];
    }
}

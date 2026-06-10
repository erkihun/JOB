<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Hard-rejects script-capable uploads (SVG, HTML, XML, JS, PHP) regardless of the
 * configurable `recruitment.allowed_file_types` allow-list, so a stored-XSS or
 * code-execution vector can never be uploaded even if an administrator mistakenly
 * adds a dangerous type to the allow-list.
 *
 * Checks BOTH the client extension and the guessed MIME type, so a renamed file
 * (e.g. payload.svg → payload.pdf carrying SVG content) is still caught.
 */
final class SafeUploadRule implements ValidationRule
{
    private const BLOCKED_EXTENSIONS = [
        'svg', 'svgz', 'html', 'htm', 'xml', 'xhtml', 'js', 'mjs',
        'php', 'phtml', 'phar', 'phps', 'pht', 'htaccess',
    ];

    private const BLOCKED_MIMES = [
        'image/svg+xml', 'text/html', 'application/xml', 'text/xml',
        'application/xhtml+xml', 'application/javascript', 'text/javascript',
        'application/x-php', 'text/x-php', 'application/x-httpd-php',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $ext = strtolower($value->getClientOriginalExtension());
        $mime = strtolower((string) ($value->getMimeType() ?? $value->getClientMimeType()));

        if (in_array($ext, self::BLOCKED_EXTENSIONS, true) || in_array($mime, self::BLOCKED_MIMES, true)) {
            $allowed = implode(', ', (array) Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png']));
            $fail(__('validation.mimes', [
                'attribute' => __('documents.type_documents'),
                'values' => $allowed,
            ]));
        }
    }
}

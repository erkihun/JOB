<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Models\ApplicationDocument;
use App\Models\Setting;
use App\Rules\SafeUploadRule;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ApplicationDocument|null $document */
        $document = $this->route('document');

        return $this->user()->can('replace', $document);
    }

    public function rules(): array
    {
        /** @var ApplicationDocument|null $document */
        $document = $this->route('document');
        $vacancyDoc = $document?->vacancyDocument;

        $maxKb = (($vacancyDoc?->max_size_mb) ?? Setting::get('recruitment.max_file_size_mb', 2)) * 1024;
        $mimes = implode(',', $vacancyDoc?->allowed_types ?? (array) Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png']));

        return [
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxKb}", new SafeUploadRule],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => __('applications.invalid_file_type'),
            'file.max' => __('applications.file_too_large'),
        ];
    }
}

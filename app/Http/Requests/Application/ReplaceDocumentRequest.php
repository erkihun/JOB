<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Models\ApplicationDocument;
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

        $maxKb = (($vacancyDoc?->max_size_mb) ?? 2) * 1024;
        $mimes = implode(',', $vacancyDoc?->allowed_types ?? ['pdf', 'jpg', 'jpeg', 'png']);

        return [
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxKb}"],
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

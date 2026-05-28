<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacancyAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject'      => ['required', 'string', 'max:255'],
            'content'      => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('_pub_date')) {
            $time = $this->input('_pub_time', '00:00') ?: '00:00';
            $this->merge(['published_at' => $this->input('_pub_date') . ' ' . $time . ':00']);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Rules\HttpOrHttpsUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHeroSliderRequest extends FormRequest
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
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'subtitle_am' => ['nullable', 'string', 'max:500'],
            'button_text_en' => ['nullable', 'string', 'max:100'],
            'button_text_am' => ['nullable', 'string', 'max:100'],
            'button_link' => ['nullable', 'string', 'max:500', 'url', new HttpOrHttpsUrl],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }
}

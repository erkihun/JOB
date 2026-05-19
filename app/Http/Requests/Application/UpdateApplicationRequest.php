<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application|null $application */
        $application = $this->route('application');

        return $this->user()->can('update', $application);
    }

    public function rules(): array
    {
        return [
            'field_of_study' => ['required', 'string', 'max:255'],
            'graduation_date' => ['required', 'date', 'before_or_equal:today'],
            'cgpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Models\Application;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class UpdateApplicationAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Application $application, array $data): Application
    {
        $attributes = [
            'field_of_study' => $data['field_of_study'],
            'graduation_date' => $data['graduation_date'],
            'cgpa' => $data['cgpa'] ?? null,
            'last_updated_at' => now(),
        ];

        // Optional position switch: move the application to a different open vacancy.
        if (! empty($data['vacancy_id']) && $data['vacancy_id'] !== $application->vacancy_id) {
            $attributes['vacancy_id'] = $data['vacancy_id'];
        }

        try {
            $application->update($attributes);
        } catch (UniqueConstraintViolationException) {
            // Race: another application for (applicant, target vacancy) already exists.
            throw ValidationException::withMessages([
                'vacancy_id' => [__('applications.duplicate_application')],
            ]);
        }

        return $application->fresh();
    }
}

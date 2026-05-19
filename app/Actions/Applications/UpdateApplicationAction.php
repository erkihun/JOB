<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Models\Application;

class UpdateApplicationAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Application $application, array $data): Application
    {
        $application->update([
            'field_of_study' => $data['field_of_study'],
            'graduation_date' => $data['graduation_date'],
            'cgpa' => $data['cgpa'] ?? null,
            'last_updated_at' => now(),
        ]);

        return $application->fresh();
    }
}

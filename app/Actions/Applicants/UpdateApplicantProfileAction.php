<?php

declare(strict_types=1);

namespace App\Actions\Applicants;

use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateApplicantProfileAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Applicant $applicant, array $data): Applicant
    {
        $fullName = trim(implode(' ', array_filter([
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
        ])));

        // Replace profile photo if a new one was uploaded
        $photoPath = $applicant->profile_photo_path;
        if (isset($data['profile_photo']) && $data['profile_photo'] instanceof UploadedFile) {
            // Delete old photo
            if ($photoPath && Storage::disk('local')->exists($photoPath)) {
                Storage::disk('local')->delete($photoPath);
            }
            $uuid = (string) Str::orderedUuid();
            $ext = $data['profile_photo']->getClientOriginalExtension();
            $photoPath = $data['profile_photo']->storeAs(
                'applicants/photos/'.$applicant->user_id,
                "$uuid.$ext",
                'local',
            );
        }

        $applicant->update([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'full_name' => $fullName,
            'phone' => $data['phone'],
            'alternative_phone' => $data['alternative_phone'] ?? null,
            'email' => $data['email'],
            'national_id' => $data['national_id'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'disability_status' => (bool) ($data['disability_status'] ?? false),
            'disability_type' => $data['disability_type'] ?? null,
            'university_name' => $data['university_name'] ?? null,
            'field_of_study' => $data['field_of_study'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'graduation_date' => $data['graduation_date'] ?? null,
            'gpa' => $data['gpa'] ?? null,
            'education_level' => $data['education_level'] ?? null,
            'work_experience_years' => (int) ($data['work_experience_years'] ?? 0),
            'work_experience_months' => isset($data['work_experience_months'])
                ? (int) $data['work_experience_months'] : null,
            'current_employer' => $data['current_employer'] ?? null,
            'current_position' => $data['current_position'] ?? null,
            'work_experience_summary' => $data['work_experience_summary'] ?? null,
            'address' => $data['address'] ?? null,
            'ethnicity' => $data['ethnicity'] ?? null,
            'preferred_locale' => $data['preferred_locale'],
            'profile_photo_path' => $photoPath,
        ]);

        // Keep user name and locale in sync
        $applicant->user->update([
            'name' => $fullName,
            'preferred_locale' => $data['preferred_locale'],
        ]);

        Session::put('locale', $data['preferred_locale']);

        $this->replaceDocuments($applicant, $data);

        return $applicant->fresh();
    }

    /** Replace the combined documents PDF when a new one is uploaded. */
    private function replaceDocuments(Applicant $applicant, array $data): void
    {
        $file = $data['documents'] ?? null;
        if (! $file instanceof UploadedFile) {
            return;
        }

        // Delete existing combined document if present
        $existing = $applicant->profileDocuments()->where('document_type', 'documents')->first();
        if ($existing) {
            Storage::disk('local')->delete($existing->file_path);
            $existing->delete();
        }

        $uuid = (string) Str::orderedUuid();
        $ext = $file->getClientOriginalExtension();
        $filePath = $file->storeAs(
            'applicant-documents/'.$applicant->id,
            "$uuid.$ext",
            'local',
        );

        ApplicantProfileDocument::create([
            'applicant_id' => $applicant->id,
            'document_type' => 'documents',
            'file_name' => "$uuid.$ext",
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}

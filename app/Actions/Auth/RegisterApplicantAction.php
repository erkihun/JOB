<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterApplicantAction
{
    /**
     * @param  array<string, mixed>  $data  Validated request data (includes UploadedFile instances)
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $fullName = $this->buildFullName($data);

            $user = User::create([
                'name' => $fullName,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'preferred_locale' => $data['preferred_locale'],
            ]);

            $user->assignRole('applicant');

            $applicant = Applicant::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'full_name' => $fullName,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'alternative_phone' => $data['alternative_phone'] ?? null,
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
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'woreda' => $data['woreda'] ?? null,
                'address' => $data['address'] ?? null,
                'preferred_locale' => $data['preferred_locale'],
                'profile_photo_path' => null,
            ]);

            $this->storeProfileDocuments($applicant, $data);

            return $user;
        });
    }

    /** Build full name from name parts. */
    private function buildFullName(array $data): string
    {
        return trim(implode(' ', array_filter([
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
        ])));
    }

    /** Store the combined documents PDF if uploaded. */
    private function storeProfileDocuments(Applicant $applicant, array $data): void
    {
        $file = $data['documents'] ?? null;
        if (! $file instanceof UploadedFile) {
            return;
        }

        $uuid = (string) Str::orderedUuid();
        $ext = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'applicant-documents/'.$applicant->id,
            "$uuid.$ext",
            'local',
        );

        ApplicantProfileDocument::create([
            'applicant_id' => $applicant->id,
            'document_type' => 'documents',
            'file_name' => "$uuid.$ext",
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}

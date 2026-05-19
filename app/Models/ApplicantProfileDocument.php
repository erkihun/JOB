<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantProfileDocument extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $table = 'applicant_profile_documents';

    protected $fillable = [
        'applicant_id',
        'document_type',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function getFileSizeMbAttribute(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }
}

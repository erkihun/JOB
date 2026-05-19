<?php

namespace App\Models;

use App\Enums\DocumentVerificationStatus;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $fillable = [
        'application_id',
        'vacancy_document_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'verification_status',
        'verification_remark',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verification_status' => DocumentVerificationStatus::class,
            'verified_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function vacancyDocument(): BelongsTo
    {
        return $this->belongsTo(VacancyDocument::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getFileSizeMbAttribute(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }
}

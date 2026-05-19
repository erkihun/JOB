<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantNotification extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $table = 'applicant_notifications';

    protected $fillable = [
        'applicant_id',
        'application_id',
        'type',
        'channel',
        'subject',
        'message',
        'status',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}

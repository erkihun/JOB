<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupRecord extends Model
{
    use HasOrderedUuid;

    protected $fillable = ['type', 'destination', 'disk', 'path', 'size', 'status', 'started_at', 'completed_at', 'initiated_by', 'checksum', 'failure_message', 'options'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'options' => 'array'];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}

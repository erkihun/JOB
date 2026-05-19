<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory, HasOrderedUuid;

    protected $table = 'notification_templates';

    protected $fillable = [
        'type',
        'locale',
        'subject',
        'body',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'active' => 'boolean',
        ];
    }

    public static function findForType(NotificationType $type, string $locale = 'en'): ?static
    {
        return static::where('type', $type->value)
            ->where('locale', $locale)
            ->where('active', true)
            ->first();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasOrderedUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class Setting extends Model
{
    use HasFactory, HasOrderedUuid;

    /**
     * @var array<string, array{value: string, type: string}>|null
     */
    private static ?array $cachedSettings = null;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::cached()[$key] ?? null;

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting['value'], $setting['type']);
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null): void
    {
        $encodedValue = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $encodedValue, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
        self::$cachedSettings = null;
    }

    /**
     * @return array<string, array{value: string, type: string}>
     */
    private static function cached(): array
    {
        if (app()->environment('testing')) {
            return static::loadSettings();
        }

        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        try {
            self::$cachedSettings = Cache::rememberForever('settings.all', fn (): array => static::loadSettings());
        } catch (Throwable) {
            self::$cachedSettings = static::loadSettings();
        }

        return self::$cachedSettings;
    }

    /**
     * @return array<string, array{value: string, type: string}>
     */
    private static function loadSettings(): array
    {
        return static::query()
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(fn (self $setting): array => [
                $setting->key => [
                    'value' => (string) $setting->value,
                    'type' => (string) $setting->type,
                ],
            ])
            ->all();
    }

    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true) ?: [],
            default => $value,
        };
    }
}

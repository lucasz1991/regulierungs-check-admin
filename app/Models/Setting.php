<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    /** 
     * Cache prefix, um Keys konsistent zu halten.
     */
    protected static $cachePrefix = 'settings.';

    /**
     * Get the value of a setting by type and key (cached).
     */
    public static function getValue($type, $key)
    {
        $cacheKey = static::$cachePrefix . "{$type}.{$key}";

        // Cache für 1 Stunde (kannst du anpassen)
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($type, $key) {
            return optional(
                static::where('type', $type)
                    ->where('key', $key)
                    ->first()
            )->value;
        });
    }

    /**
     * Set or update the value of a setting by key
     * and clear the cache automatically.
     */
    public static function setValue($type, $key, $value)
    {
        $setting = static::updateOrCreate(
            ['type' => $type, 'key' => $key],
            ['value' => $value]
        );

        // Cache-Eintrag invalidieren
        $cacheKey = static::$cachePrefix . "{$type}.{$key}";
        Cache::forget($cacheKey);

        return $setting;
    }

    public static function enabled(string $type, string $key, bool $default = false): bool
    {
        $value = static::where('type', $type)->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            $value = $value['enabled'] ?? $value['value'] ?? reset($value);
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Löscht den Cache für einen kompletten Setting-Type.
     */
    public static function clearTypeCache($type)
    {
        // Optional: falls du mehrere Keys pro Type hast
        $pattern = static::$cachePrefix . "{$type}.*";
        collect(Cache::getRedis()->keys($pattern))
            ->each(fn($key) => Cache::forget(str_replace(config('cache.prefix') . ':', '', $key)));
    }
}

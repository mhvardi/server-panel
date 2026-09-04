<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SecuritySetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("security_setting_{$key}", 300, function () use ($key, $default) {
            $setting = static::find($key);
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value and clear cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget("security_setting_{$key}");
    }

    /**
     * Check if a boolean setting is true.
     */
    public static function isTrue(string $key, bool $default = false): bool
    {
        $val = static::get($key, $default ? 'true' : 'false');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}

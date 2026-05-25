<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Read/write helper for runtime configuration that lives in DB
 * instead of .env. Sensitive keys (anything whose name ends in
 * `_secret`, `_key`, `_token`, `_password`) is automatically
 * encrypted at rest via Laravel's Crypt.
 *
 * Values are cached forever and busted on every set() — the
 * cache key is namespaced so a flush of cache:clear keeps the
 * settings table intact.
 *
 * Fall-through: when a key is not set, get() returns the value
 * from config('settings.defaults.<key>') so a fresh install still
 * boots with sensible defaults from config/settings.php.
 */
final class SettingsRepository
{
    private const CACHE_KEY = 'app.settings.v1';

    /** Sensitive key suffixes that get encrypted at rest. */
    private const ENCRYPT_SUFFIXES = ['_secret', '_token', '_password', '_key'];

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $out = [];
            foreach (AppSetting::query()->get() as $row) {
                $out[$row->key] = $this->decode($row);
            }

            return $out;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return config('settings.defaults.'.$key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $shouldEncrypt = $this->shouldEncrypt($key);
        $stored = $value === null || $value === ''
            ? null
            : ($shouldEncrypt ? Crypt::encryptString((string) $value) : (string) $value);

        AppSetting::query()->updateOrInsert(
            ['key' => $key],
            [
                'value' => $stored,
                'is_encrypted' => $shouldEncrypt,
                'updated_at' => now(),
            ],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @param  array<string, mixed>  $values */
    public function setMany(array $values): void
    {
        foreach ($values as $k => $v) {
            $this->set((string) $k, $v);
        }
    }

    public function forget(string $key): void
    {
        AppSetting::query()->where('key', $key)->delete();
        Cache::forget(self::CACHE_KEY);
    }

    private function decode(AppSetting $row): ?string
    {
        if ($row->value === null) {
            return null;
        }
        if (! $row->is_encrypted) {
            return $row->value;
        }
        try {
            return Crypt::decryptString($row->value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function shouldEncrypt(string $key): bool
    {
        foreach (self::ENCRYPT_SUFFIXES as $suffix) {
            if (str_ends_with($key, $suffix)) {
                return true;
            }
        }

        return false;
    }
}

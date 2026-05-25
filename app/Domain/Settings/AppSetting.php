<?php

declare(strict_types=1);

namespace App\Domain\Settings;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property ?string $value
 * @property bool $is_encrypted
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
final class AppSetting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'is_encrypted', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'bool',
            'updated_at' => 'datetime',
        ];
    }
}

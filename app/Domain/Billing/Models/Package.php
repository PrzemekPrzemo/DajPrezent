<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property int $price_pln_gr
 * @property int $valid_days
 * @property ?int $gift_limit
 * @property array $features
 * @property bool $is_active
 */
final class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'kind', 'price_pln_gr',
        'valid_days', 'gift_limit', 'features', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'bool',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }

    public function featureValue(string $key): mixed
    {
        return $this->features[$key] ?? null;
    }

    public function priceInPln(): float
    {
        return $this->price_pln_gr / 100;
    }
}

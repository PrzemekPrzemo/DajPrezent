<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property ?string $description
 * @property ?string $image_path
 * @property ?string $url
 * @property ?int $price_pln_gr
 * @property int $priority
 * @property ?string $category
 * @property string $status
 * @property int $position
 */
final class Gift extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'tenant_id', 'title', 'description', 'image_path', 'url',
        'price_pln_gr', 'priority', 'category', 'status', 'position',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(GiftReservation::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}

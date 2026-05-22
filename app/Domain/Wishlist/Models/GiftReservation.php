<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $gift_id
 * @property string $guest_email
 * @property ?string $guest_name
 * @property string $intent
 * @property string $status
 * @property string $verification_token
 * @property ?Carbon $email_verified_at
 * @property ?Carbon $expires_at
 * @property ?Carbon $cancelled_at
 * @property ?string $ip
 */
final class GiftReservation extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'tenant_id', 'gift_id', 'guest_email', 'guest_name',
        'intent', 'status', 'verification_token',
        'email_verified_at', 'expires_at', 'cancelled_at', 'ip',
    ];

    protected $hidden = ['guest_email', 'guest_name', 'verification_token', 'ip'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }
}

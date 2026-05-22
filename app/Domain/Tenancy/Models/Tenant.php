<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Models;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_user_id
 * @property string $slug
 * @property string $name
 * @property string $kind
 * @property string $locale
 * @property ?string $password_hash
 * @property ?string $cover_image_path
 * @property ?array $theme
 * @property ?Carbon $expires_at
 * @property bool $is_public
 */
final class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'owner_user_id', 'slug', 'name', 'kind', 'locale',
        'password_hash', 'cover_image_path', 'theme',
        'expires_at', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'is_public' => 'bool',
            'expires_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPasswordProtected(): bool
    {
        return $this->password_hash !== null;
    }
}

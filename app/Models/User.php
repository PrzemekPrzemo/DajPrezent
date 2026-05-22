<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_master_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_master_admin' => 'bool',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->is_master_admin === true,
            default => false,
        };
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_user_id');
    }

    public function ownsTenant(Tenant $tenant): bool
    {
        return $tenant->owner_user_id === $this->id;
    }
}

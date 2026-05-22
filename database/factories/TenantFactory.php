<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->name(),
            'kind' => 'wishlist',
            'locale' => 'pl',
            'expires_at' => now()->addMonths(9),
            'is_public' => true,
        ];
    }
}

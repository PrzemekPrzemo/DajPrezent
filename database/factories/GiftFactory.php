<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gift>
 */
final class GiftFactory extends Factory
{
    protected $model = Gift::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'price_pln_gr' => $this->faker->numberBetween(2000, 50000),
            'priority' => 2,
            'status' => Gift::STATUS_AVAILABLE,
            'position' => 0,
        ];
    }
}

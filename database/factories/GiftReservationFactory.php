<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GiftReservation>
 */
final class GiftReservationFactory extends Factory
{
    protected $model = GiftReservation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'gift_id' => Gift::factory(),
            'guest_email' => $this->faker->safeEmail(),
            'guest_name' => $this->faker->firstName(),
            'intent' => 'reserve',
            'status' => GiftReservation::STATUS_PENDING,
            'verification_token' => Str::random(48),
            'expires_at' => now()->addMinutes(60),
        ];
    }
}

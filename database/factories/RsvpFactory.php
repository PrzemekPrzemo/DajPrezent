<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\Rsvp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rsvp>
 */
final class RsvpFactory extends Factory
{
    protected $model = Rsvp::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'guest_name' => $this->faker->firstName().' '.$this->faker->lastName(),
            'guest_email' => $this->faker->safeEmail(),
            'attending' => true,
            'plus_one' => false,
        ];
    }
}

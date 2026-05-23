<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\WeddingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingEvent>
 */
final class WeddingEventFactory extends Factory
{
    protected $model = WeddingEvent::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'couple_names' => 'Anna & Tomek',
            'hashtag' => '#AnnaITomek2026',
            'ceremony_at' => now()->addMonths(6),
            'venue_name' => 'Pałac w Łazienkach',
            'venue_address' => 'Agrykola 1, 00-460 Warszawa',
            'dress_code' => 'cocktail attire',
            'theme' => 'classic',
        ];
    }
}

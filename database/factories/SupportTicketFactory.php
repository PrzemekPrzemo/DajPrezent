<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Support\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
final class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement(SupportTicket::CATEGORIES),
            'priority' => 'normal',
            'subject' => $this->faker->sentence(5),
            'body' => $this->faker->paragraph(3),
            'status' => 'open',
        ];
    }
}

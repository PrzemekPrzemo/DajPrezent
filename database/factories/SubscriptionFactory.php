<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
final class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'package_id' => fn () => Package::factory()->create()->id,
            'status' => 'pending',
            'amount_pln_gr' => 6900,
        ];
    }
}

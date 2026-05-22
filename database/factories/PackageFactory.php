<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
final class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'code' => 'pkg_'.Str::random(8),
            'name' => $this->faker->word(),
            'kind' => 'standard',
            'price_pln_gr' => 6900,
            'valid_days' => 270,
            'gift_limit' => 75,
            'features' => [],
            'is_active' => true,
        ];
    }
}

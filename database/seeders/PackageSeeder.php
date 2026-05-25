<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Billing\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

/**
 * Idempotent seeder that mirrors config/packages.php into the database.
 *
 * Run on every deploy so price/limit changes in config propagate without
 * a manual migration. Existing packages are updated by `code`.
 */
final class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            ...Config::array('packages.standard'),
            ...Config::array('packages.wedding'),
        ];

        foreach ($sets as $code => $data) {
            Package::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'kind' => $data['kind'],
                    'price_pln_gr' => $data['price_pln_gr'],
                    'valid_days' => $data['valid_days'],
                    'gift_limit' => $data['gift_limit'] ?? null,
                    'features' => $data,
                    // VIP is admin-only — never shown in /pakiety. The
                    // pricing controller filters by is_active=true.
                    'is_active' => $code !== 'vip',
                ]
            );
        }
    }
}

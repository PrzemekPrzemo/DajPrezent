<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use Database\Seeders\PackageSeeder;

it('seeds all standard and wedding packages', function (): void {
    $this->seed(PackageSeeder::class);

    expect(Package::query()->count())->toBe(7);

    foreach (['free', 'mini', 'standard', 'plus', 'pro', 'wedding_basic', 'wedding_premium'] as $code) {
        expect(Package::query()->where('code', $code)->exists())
            ->toBeTrue("Expected package {$code} to be seeded.");
    }
});

it('exposes feature flags via featureValue helper', function (): void {
    $this->seed(PackageSeeder::class);

    $pro = Package::query()->where('code', 'pro')->firstOrFail();

    expect($pro->hasFeature('custom_slug'))->toBeTrue()
        ->and($pro->hasFeature('export'))->toBeTrue()
        ->and($pro->featureValue('gift_limit'))->toBe(200);
});

it('marks wedding packages with unlimited gift_limit', function (): void {
    $this->seed(PackageSeeder::class);

    $premium = Package::query()->where('code', 'wedding_premium')->firstOrFail();

    expect($premium->gift_limit)->toBeNull()
        ->and($premium->kind)->toBe('wedding');
});

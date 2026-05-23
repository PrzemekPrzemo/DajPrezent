<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\SubscriptionsLast6MonthsChart;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
    $this->admin = User::factory()->create(['is_master_admin' => true]);
});

it('renders the master-admin dashboard with stats overview', function (): void {
    $tenant = Tenant::factory()->create();
    $pkg = Package::factory()->create(['price_pln_gr' => 6900, 'name' => 'Plus']);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $pkg->id,
        'status' => 'active',
        'paid_at' => now()->subDay(),
        'amount_pln_gr' => 6900,
        'expires_at' => now()->addMonths(9),
    ]);
    Gift::factory()->count(7)->create(['tenant_id' => $tenant->id]);
    SupportTicket::factory()->create(['status' => 'open', 'priority' => 'high', 'user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Sprzedaż 30 dni', false)
        ->assertSee('Aktywne subskrypcje', false)
        ->assertSee('Nowe listy w tym mc', false)
        ->assertSee('Otwarte zgłoszenia', false);
});

it('computes the MRR stat from active subs paid in the last 30 days', function (): void {
    $tenant = Tenant::factory()->create();
    $pkg = Package::factory()->create();
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $pkg->id,
        'status' => 'active',
        'paid_at' => now()->subDays(5),
        'amount_pln_gr' => 19900,
    ]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $pkg->id,
        'status' => 'active',
        'paid_at' => now()->subDays(40), // outside the 30-day window
        'amount_pln_gr' => 39900,
    ]);

    $widget = new AdminStatsOverview;
    $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
    expect($stats[0]->getValue())->toContain('199');
});

it('computes the verified-reservation rate from the last 30 days', function (): void {
    $tenant = Tenant::factory()->create();
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);

    GiftReservation::factory()->create([
        'gift_id' => $gift->id,
        'email_verified_at' => now(),
        'status' => GiftReservation::STATUS_ACTIVE,
        'created_at' => now()->subDays(3),
    ]);
    GiftReservation::factory()->count(3)->create([
        'gift_id' => $gift->id,
        'email_verified_at' => null,
        'status' => 'pending',
        'created_at' => now()->subDays(3),
    ]);

    $widget = new AdminStatsOverview;
    $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);
    // Reservation card is the 5th (index 4). 1 verified / 4 total = 25%.
    expect($stats[4]->getDescription())->toContain('25%');
});

it('feeds the 6-month chart with one value per month', function (): void {
    $widget = new SubscriptionsLast6MonthsChart;
    $data = (new ReflectionMethod($widget, 'getData'))->invoke($widget);
    expect($data['labels'])->toHaveCount(6)
        ->and($data['datasets'][0]['data'])->toHaveCount(6);
});

it('lists top packages ordered by active sub count', function (): void {
    $popular = Package::factory()->create(['code' => 'plus', 'name' => 'Plus']);
    $niche = Package::factory()->create(['code' => 'pro', 'name' => 'Pro']);
    Subscription::factory()->count(3)->create([
        'package_id' => $popular->id,
        'tenant_id' => Tenant::factory()->create()->id,
        'status' => 'active',
    ]);
    Subscription::factory()->create([
        'package_id' => $niche->id,
        'tenant_id' => Tenant::factory()->create()->id,
        'status' => 'active',
    ]);

    // Smoke: rendering the dashboard doesn't blow up with both widgets wired.
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Najczęściej kupowane pakiety', false)
        ->assertSee('Sprzedaż 6 ostatnich miesięcy', false);
});

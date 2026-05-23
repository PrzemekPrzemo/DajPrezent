<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\AddSiblingListService;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\CsvCell;
use App\Domain\Wishlist\GiftLimitGuard;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
});

/* ----- HIGH #1: GiftLimitGuard stale parent_subscription_id ----- */

it('GiftLimitGuard returns the latest active sub when parent_subscription points at an old expired one', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);

    $oldPkg = Package::factory()->create(['code' => 'standard', 'gift_limit' => 30]);
    $oldSub = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $oldPkg->id,
        'status' => 'expired',
        'paid_at' => now()->subYears(2),
        'expires_at' => now()->subYear(),
    ]);

    $newPkg = Package::factory()->create(['code' => 'plus', 'gift_limit' => 75]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $newPkg->id,
        'status' => 'active',
        'paid_at' => now()->subWeek(),
        'expires_at' => now()->addMonths(8),
    ]);

    // Simulate the old backfill that linked to the OLD subscription.
    $tenant->update(['parent_subscription_id' => $oldSub->id]);

    $guard = app(GiftLimitGuard::class);
    expect($guard->activePackage($tenant->fresh())?->gift_limit)->toBe(75);
});

/* ----- HIGH #2: CSV formula injection ----- */

it('CsvCell strips leading =/+/-/@ on import', function (): void {
    expect(CsvCell::sanitiseImport('=HYPERLINK("evil")'))->toBe('HYPERLINK("evil")')
        ->and(CsvCell::sanitiseImport('+1+cmd|x'))->toBe('1+cmd|x')
        ->and(CsvCell::sanitiseImport('-2+3'))->toBe('2+3')
        ->and(CsvCell::sanitiseImport('@SUM(A1:A9)'))->toBe('SUM(A1:A9)')
        ->and(CsvCell::sanitiseImport("\t=evil"))->toBe('evil') // tab THEN = stripped
        ->and(CsvCell::sanitiseImport('Aparat Instax'))->toBe('Aparat Instax')
        ->and(CsvCell::sanitiseImport(null))->toBeNull();
});

it('CsvCell prefixes dangerous cells with a single quote on export', function (): void {
    expect(CsvCell::sanitiseExport('=evil()'))->toBe("'=evil()")
        ->and(CsvCell::sanitiseExport('Aparat'))->toBe('Aparat')
        ->and(CsvCell::sanitiseExport(''))->toBe('')
        ->and(CsvCell::sanitiseExport(null))->toBe('');
});

it('CSV import sanitises formula-injection in title and description', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 100])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $csv = "tytuł;opis\n=HYPERLINK(\"https://evil\");+cmd|calc!A1\n";
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $csv);
    $file = new UploadedFile($path, 'gifts.csv', 'text/csv', null, true);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$tenant->id}/gifts/import", ['csv' => $file])
        ->assertRedirect();

    $gift = Gift::query()->firstOrFail();
    expect($gift->title)->toBe('HYPERLINK("https://evil")')
        ->and($gift->description)->toBe('cmd|calc!A1');
});

/* ----- MEDIUM #3: AddSiblingListService race + ownership ----- */

it('AddSiblingListService::create refuses a parent subscription owned by a different user', function (): void {
    $stranger = User::factory()->create();
    $strangerTenant = Tenant::factory()->create(['owner_user_id' => $stranger->id]);
    $strangerSub = Subscription::factory()->create([
        'tenant_id' => $strangerTenant->id,
        'package_id' => Package::factory()->create(['features' => ['multiple_lists' => 3]])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $service = app(AddSiblingListService::class);
    expect(fn () => $service->create($this->owner, $strangerSub, 'evil', 'Evil'))
        ->toThrow(LogicException::class);
});

/* ----- MEDIUM #4: is_public default ----- */

it('AddSiblingListService creates siblings as private by default', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $sub = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['features' => ['multiple_lists' => 3]])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
    $tenant->update(['parent_subscription_id' => $sub->id]);

    $sibling = app(AddSiblingListService::class)
        ->create($this->owner, $sub->fresh(), 'siostra', 'Siostra lista');

    expect((bool) $sibling->is_public)->toBeFalse();
});

/* ----- MEDIUM #5: import throttle ----- */

it('throttles import endpoint after 5 requests/hour', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 100])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $makeCsv = function (): UploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "tytuł\nA\n");

        return new UploadedFile($path, 'gifts.csv', 'text/csv', null, true);
    };

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($this->owner)
            ->post("/panel/lists/{$tenant->id}/gifts/import", ['csv' => $makeCsv()])
            ->assertRedirect();
    }
    // 6th must be throttled.
    $this->actingAs($this->owner)
        ->post("/panel/lists/{$tenant->id}/gifts/import", ['csv' => $makeCsv()])
        ->assertStatus(429);
});

/* ----- MEDIUM #6: eligibleSubscription picks pakiet with MOST free slots ----- */

it('eligibleSubscription picks the sub with the most free slots, not the newest paid', function (): void {
    // Newer Plus (3 slots, all used) + older Pro (5 slots, 1 used) = should pick Pro.
    $proPkg = Package::factory()->create(['features' => ['multiple_lists' => 5]]);
    $plusPkg = Package::factory()->create(['features' => ['multiple_lists' => 3]]);

    $proTenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $proSub = Subscription::factory()->create([
        'tenant_id' => $proTenant->id,
        'package_id' => $proPkg->id,
        'status' => 'active',
        'paid_at' => now()->subMonths(3),
        'expires_at' => now()->addMonths(6),
    ]);
    $proTenant->update(['parent_subscription_id' => $proSub->id]);

    $plusTenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $plusSub = Subscription::factory()->create([
        'tenant_id' => $plusTenant->id,
        'package_id' => $plusPkg->id,
        'status' => 'active',
        'paid_at' => now()->subDays(2),
        'expires_at' => now()->addMonths(9),
    ]);
    $plusTenant->update(['parent_subscription_id' => $plusSub->id]);

    // Fill Plus to its limit.
    Tenant::factory()->count(2)->create([
        'owner_user_id' => $this->owner->id,
        'parent_subscription_id' => $plusSub->id,
    ]);
    // Plus: 3/3 used, Pro: 1/5 used. Eligible should return Pro.

    $picked = app(AddSiblingListService::class)->eligibleSubscription($this->owner);
    expect($picked?->id)->toBe($proSub->id);
});

it('eligibleSubscription works even after primary tenant was soft-deleted', function (): void {
    $primary = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $sub = Subscription::factory()->create([
        'tenant_id' => $primary->id,
        'package_id' => Package::factory()->create(['features' => ['multiple_lists' => 3]])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
    $primary->update(['parent_subscription_id' => $sub->id]);

    $primary->delete(); // soft-delete

    $picked = app(AddSiblingListService::class)->eligibleSubscription($this->owner);
    expect($picked?->id)->toBe($sub->id);
});

it('eligibleSubscription ignores pending (paid_at NULL) subscriptions', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['features' => ['multiple_lists' => 3]])->id,
        'status' => 'active',
        'paid_at' => null, // <- never actually paid
        'expires_at' => now()->addMonths(9),
    ]);

    expect(app(AddSiblingListService::class)->eligibleSubscription($this->owner))->toBeNull();
});

/* ----- LOW #7: parsePriority no longer coerces "13" to 1 ----- */

it('CSV import parses "13" / "1-3" / "31" as priority 2 (fallback), not 1', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 100])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $csv = "tytuł;priorytet\nA;13\nB;31\nC;1\nD;3\nE;fajnie\n";
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $csv);
    $file = new UploadedFile($path, 'gifts.csv', 'text/csv', null, true);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$tenant->id}/gifts/import", ['csv' => $file])
        ->assertRedirect();

    $gifts = Gift::query()->orderBy('id')->get(['title', 'priority']);
    expect($gifts[0]->priority)->toBe(2)   // "13" → fallback
        ->and($gifts[1]->priority)->toBe(2) // "31" → fallback
        ->and($gifts[2]->priority)->toBe(1) // "1"  → high
        ->and($gifts[3]->priority)->toBe(3) // "3"  → low
        ->and($gifts[4]->priority)->toBe(3); // "fajnie" → low
});

/* ----- LOW #10: reorder deduplicates ids ----- */

it('reorder deduplicates ids in the payload (no two updates on the same gift)', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 100])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $a = Gift::factory()->create(['tenant_id' => $tenant->id, 'position' => 1]);
    $b = Gift::factory()->create(['tenant_id' => $tenant->id, 'position' => 2]);
    $c = Gift::factory()->create(['tenant_id' => $tenant->id, 'position' => 3]);

    $this->actingAs($this->owner)
        ->postJson("/panel/lists/{$tenant->id}/gifts/reorder", [
            // duplicate of $a id
            'ids' => [$a->id, $b->id, $a->id, $c->id],
        ])
        ->assertOk();

    // Dedup → positions are a=1, b=2, c=3 (each unique).
    expect($a->fresh()->position)->toBe(1)
        ->and($b->fresh()->position)->toBe(2)
        ->and($c->fresh()->position)->toBe(3);
});

/* ----- LOW: CSV import bulk creates contiguous positions ----- */

it('CSV import leaves no gaps in position when partial overflow happens', function (): void {
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 3])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $csv = "tytuł\nA\nB\nC\nD\nE\n";
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $csv);
    $file = new UploadedFile($path, 'gifts.csv', 'text/csv', null, true);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$tenant->id}/gifts/import", ['csv' => $file])
        ->assertRedirect();

    $positions = Gift::query()->orderBy('position')->pluck('position')->all();
    // 3 rows imported (limit), positions strictly 1,2,3 — no gaps.
    expect($positions)->toBe([1, 2, 3]);
});

<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
});

function activeSubFor(Tenant $tenant, ?int $limit = 200): Subscription
{
    return Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => $limit])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
}

function csvFile(string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $content);

    return new UploadedFile($path, 'gifts.csv', 'text/csv', null, true);
}

it('shows the import form', function (): void {
    activeSubFor($this->tenant);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts/import")
        ->assertOk()
        ->assertSee('Import prezentów', false)
        ->assertSee('Akceptujemy nagłówki PL', false);
});

it('imports a typical PL CSV (semicolon, PL headers, "299,99" price)', function (): void {
    activeSubFor($this->tenant);

    $csv = "tytuł;cena;link;priorytet\n".
        "Aparat Instax mini;299,99;https://allegro.pl/abc;1\n".
        "Książka Atomic Habits;49,90;;2\n".
        "Monstera Deliciosa;89;;fajnie\n";

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/import", ['csv' => csvFile($csv)])
        ->assertRedirect();

    $gifts = Gift::query()->orderBy('id')->get();
    expect($gifts)->toHaveCount(3)
        ->and($gifts[0]->title)->toBe('Aparat Instax mini')
        ->and($gifts[0]->price_pln_gr)->toBe(29999)
        ->and($gifts[0]->url)->toBe('https://allegro.pl/abc')
        ->and($gifts[0]->priority)->toBe(1)
        ->and($gifts[1]->priority)->toBe(2)
        ->and($gifts[2]->priority)->toBe(3)
        ->and($gifts[1]->url)->toBeNull();
});

it('imports a comma-separated EN CSV', function (): void {
    activeSubFor($this->tenant);

    $csv = "title,description,price,url\nTablet,For drawing,1299.00,https://example.com\n";

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/import", ['csv' => csvFile($csv)])
        ->assertRedirect();

    $g = Gift::query()->firstOrFail();
    expect($g->title)->toBe('Tablet')
        ->and($g->description)->toBe('For drawing')
        ->and($g->price_pln_gr)->toBe(129900);
});

it('skips rows that exceed the package gift_limit but imports up to the cap', function (): void {
    activeSubFor($this->tenant, limit: 2);

    $csv = "tytuł\nA\nB\nC\nD\n";

    $res = $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/import", ['csv' => csvFile($csv)])
        ->assertRedirect();

    expect(Gift::query()->count())->toBe(2);
    expect(session('status'))->toContain('Zaimportowano 2')->toContain('2 pominięto');
});

it('rejects an empty CSV', function (): void {
    activeSubFor($this->tenant);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/import", ['csv' => csvFile('')])
        ->assertSessionHasErrors('csv');
});

it('rejects a CSV without a title column', function (): void {
    activeSubFor($this->tenant);

    $csv = "foo;bar\n1;2\n";

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/import", ['csv' => csvFile($csv)])
        ->assertSessionHasErrors('csv');
});

it('refuses import on a stranger tenant', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id]);
    activeSubFor($foreign);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$foreign->id}/gifts/import", ['csv' => csvFile("tytuł\nA\n")])
        ->assertForbidden();
});

it('drops invalid URLs silently instead of failing the whole import', function (): void {
    activeSubFor($this->tenant);

    $csv = "tytuł;link\nA;not-a-url\nB;https://example.com\n";

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/import", ['csv' => csvFile($csv)])
        ->assertRedirect();

    $gifts = Gift::query()->orderBy('id')->get();
    expect($gifts[0]->url)->toBeNull()
        ->and($gifts[1]->url)->toBe('https://example.com');
});

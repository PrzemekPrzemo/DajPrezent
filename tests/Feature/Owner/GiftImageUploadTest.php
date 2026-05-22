<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
});

it('uploads an image when creating a gift', function (): void {
    $file = UploadedFile::fake()->image('present.jpg', 800, 600);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Hulajnoga',
            'priority' => 2,
            'image' => $file,
        ])
        ->assertRedirect();

    $gift = Gift::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
    expect($gift->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($gift->image_path);
    expect($gift->image_path)->toStartWith("gifts/{$this->tenant->id}/");
});

it('rejects non-image uploads', function (): void {
    $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/gifts")
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'PDF tu nie ma czego szukać',
            'priority' => 2,
            'image' => $file,
        ])
        ->assertSessionHasErrors('image');

    expect(Gift::query()->count())->toBe(0);
});

it('rejects oversized images (>4 MB)', function (): void {
    $file = UploadedFile::fake()->image('huge.jpg')->size(5_000); // 5 MB

    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/gifts")
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Too big',
            'priority' => 2,
            'image' => $file,
        ])
        ->assertSessionHasErrors('image');
});

it('replaces and deletes the old image when uploading a new one', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);
    $old = UploadedFile::fake()->image('a.jpg', 400, 400)->store('gifts/'.$this->tenant->id, 'public');
    $gift->update(['image_path' => $old]);
    Storage::disk('public')->assertExists($old);

    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}", [
            'title' => $gift->title,
            'priority' => 2,
            'image' => UploadedFile::fake()->image('b.jpg', 400, 400),
        ])
        ->assertRedirect();

    Storage::disk('public')->assertMissing($old);
    expect($gift->fresh()->image_path)
        ->not->toBeNull()
        ->not->toBe($old);
});

it('removes the image when remove_image=1 is sent', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);
    $path = UploadedFile::fake()->image('a.jpg')->store('gifts/'.$this->tenant->id, 'public');
    $gift->update(['image_path' => $path]);

    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}", [
            'title' => $gift->title,
            'priority' => 2,
            'remove_image' => '1',
        ])
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
    expect($gift->fresh()->image_path)->toBeNull();
});

it('deletes the image when the gift is deleted', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);
    $path = UploadedFile::fake()->image('a.jpg')->store('gifts/'.$this->tenant->id, 'public');
    $gift->update(['image_path' => $path]);

    $this->actingAs($this->owner)
        ->delete("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}")
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
});

it('namespaces uploads by tenant_id so blast radius is contained', function (): void {
    $other = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$other->id}/gifts", [
            'title' => 'X',
            'priority' => 2,
            'image' => UploadedFile::fake()->image('x.jpg'),
        ])
        ->assertRedirect();

    $gift = Gift::query()->where('tenant_id', $other->id)->firstOrFail();
    expect($gift->image_path)->toStartWith("gifts/{$other->id}/")
        ->not->toStartWith("gifts/{$this->tenant->id}/");
});

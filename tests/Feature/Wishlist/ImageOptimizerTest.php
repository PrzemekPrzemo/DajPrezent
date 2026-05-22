<?php

declare(strict_types=1);

use App\Domain\Wishlist\Images\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->optimizer = app(ImageOptimizer::class);
});

it('stores a small image as a WebP under gifts/{tenant_id}/', function (): void {
    $file = UploadedFile::fake()->image('small.jpg', 600, 400);

    $path = $this->optimizer->storeForTenant($file, tenantId: 42);

    expect($path)->toStartWith('gifts/42/')
        ->and($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    // Verify the stored content really is a WebP — magic bytes start with "RIFF....WEBP".
    $bytes = Storage::disk('public')->get($path);
    expect(substr($bytes, 0, 4))->toBe('RIFF');
    expect(substr($bytes, 8, 4))->toBe('WEBP');
});

it('downscales an oversize image to the MAX_EDGE constraint', function (): void {
    $file = UploadedFile::fake()->image('huge.jpg', 3000, 2000);

    $path = $this->optimizer->storeForTenant($file, tenantId: 1);

    // Read back, decode, check dimensions ≤ 1200 on the longest edge.
    $bytes = Storage::disk('public')->get($path);
    $img = imagecreatefromstring($bytes);
    expect($img)->not->toBeFalse();

    $w = imagesx($img);
    $h = imagesy($img);
    expect(max($w, $h))->toBe(ImageOptimizer::MAX_EDGE);
    // Aspect ratio preserved (3:2 → 1200x800).
    expect($w)->toBe(1200)->and($h)->toBe(800);
});

it('does NOT upscale a smaller-than-max image', function (): void {
    $file = UploadedFile::fake()->image('tiny.png', 200, 100);

    $path = $this->optimizer->storeForTenant($file, tenantId: 1);

    $bytes = Storage::disk('public')->get($path);
    $img = imagecreatefromstring($bytes);
    expect(imagesx($img))->toBe(200)->and(imagesy($img))->toBe(100);
});

it('refuses non-image bytes', function (): void {
    $file = UploadedFile::fake()->createWithContent('whatever.png', 'not really a png');

    $this->optimizer->storeForTenant($file, tenantId: 1);
})->throws(RuntimeException::class, 'not a recognisable image');

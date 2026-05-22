<?php

declare(strict_types=1);

use App\Domain\Tenancy\Rules\AllowedSlug;
use Illuminate\Support\Facades\Validator;

function validate(string $slug): array
{
    $validator = Validator::make(
        ['slug' => $slug],
        ['slug' => [new AllowedSlug]],
    );

    return $validator->errors()->get('slug');
}

it('accepts valid lowercase slugs', function (string $slug): void {
    expect(validate($slug))->toBe([]);
})->with(['anna-i-tomek', 'urodziny30', 'wesele-2026', 'a1']);

it('rejects reserved system slugs', function (string $slug): void {
    expect(validate($slug))->not->toBe([]);
})->with(['admin', 'api', 'login', 'panel', 'payu', 'webhook', 'wedding', 'wesele']);

it('rejects slugs with disallowed characters', function (string $slug): void {
    expect(validate($slug))->not->toBe([]);
})->with(['Foo', 'bar baz', 'ą', 'name_with_underscore', '-leading', 'trailing-', 'a', str_repeat('x', 41)]);

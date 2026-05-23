<?php

declare(strict_types=1);

use App\Domain\Billing\PolishNip;

it('accepts a real, checksum-valid NIP', function (string $nip): void {
    expect(PolishNip::isValid($nip))->toBeTrue();
})->with([
    '5252866457',     // Sendormeco Holding sp. z o.o.
    '525-28-66-457',  // same with separators
    '525 28 66 457',  // same with spaces
    '5260250274',     // Allegro
]);

it('rejects malformed inputs', function (string $nip): void {
    expect(PolishNip::isValid($nip))->toBeFalse();
})->with([
    '',
    '123',
    '1234567890123',
    'abcdefghij',
    '0000000000',
    '1111111111',
]);

it('rejects a NIP with a wrong checksum digit', function (): void {
    expect(PolishNip::isValid('5252866458'))->toBeFalse(); // last digit changed
});

it('normalizes input to digits only', function (): void {
    expect(PolishNip::normalize('  525-28/66 457  '))->toBe('5252866457');
});

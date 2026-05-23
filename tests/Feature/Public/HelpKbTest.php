<?php

declare(strict_types=1);

use App\Http\Controllers\Public\HelpController;

it('renders /pomoc with the full article index', function (): void {
    $body = (string) $this->get('/pomoc')->assertOk()->getContent();

    foreach (HelpController::ARTICLES as $slug => $title) {
        expect($body)->toContain($title);
        expect($body)->toContain('/pomoc/'.$slug);
    }
});

it('returns 404 for unknown article slug', function (): void {
    $this->get('/pomoc/nieistniejacy-artykul')->assertNotFound();
});

it('renders every registered article slug without errors', function (string $slug, string $title): void {
    $this->get('/pomoc/'.$slug)
        ->assertOk()
        ->assertSee($title)
        ->assertSee('kontakt@dajprezent.pl');
})->with(array_map(
    static fn (string $slug, string $title) => [$slug, $title],
    array_keys(HelpController::ARTICLES),
    array_values(HelpController::ARTICLES),
));

it('exposes Pomoc link in the public footer', function (): void {
    $body = (string) $this->get('/pakiety')->assertOk()->getContent();
    expect($body)->toContain(route('public.help.index'))->toContain('>Pomoc<');
});

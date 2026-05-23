<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Import;

/**
 * Minimal DTO carrying what an OpenGraph scrape can teach us about
 * a remote product page. All fields are best-effort and may be null.
 */
final class OpenGraphPreview
{
    public function __construct(
        public readonly string $url,
        public readonly ?string $title,
        public readonly ?int $pricePlnGr,
        public readonly ?string $imageUrl,
        public readonly ?string $description,
        public readonly string $source, // hostname (lowercase) we pulled from
    ) {}

    /** @return array{url:string,title:?string,price_pln_gr:?int,image_url:?string,description:?string,source:string} */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'price_pln_gr' => $this->pricePlnGr,
            'image_url' => $this->imageUrl,
            'description' => $this->description,
            'source' => $this->source,
        ];
    }
}

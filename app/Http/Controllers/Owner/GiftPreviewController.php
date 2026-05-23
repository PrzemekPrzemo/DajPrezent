<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Wishlist\Import\OpenGraphScraper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Owner-only endpoint that powers the "wkleiłem link, pole się
 * wypełniło" flow inside the Add-Gift drawer. The scraper itself
 * keeps the allowlist + timeout discipline; this controller just
 * validates input, applies the per-user throttle (route-level) and
 * returns JSON.
 */
final class GiftPreviewController extends Controller
{
    public function __invoke(Request $request, OpenGraphScraper $scraper): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url', 'max:1024', 'starts_with:https://,http://'],
        ]);

        $url = (string) $request->input('url');

        try {
            $preview = $scraper->preview($url);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'fallback' => true,
            ], 200);
        }

        return response()->json([
            'ok' => true,
            'preview' => $preview->toArray(),
        ]);
    }
}

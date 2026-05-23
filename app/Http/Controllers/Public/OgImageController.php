<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Dynamic 1200x630 OG preview PNGs. Two flavours:
 *   - /og.png — brand default with a configurable title/subtitle.
 *   - /og/list/{slug}.png — tenant's own list with name + URL.
 *
 * GD-rendered (no headless Chrome / wkhtmltopdf needed). Output is
 * cached 24h per cache key (title hash or slug) since these are
 * referenced by external scrapers (Facebook, Twitter) that hit them
 * once per share and shouldn't trigger a fresh render every time.
 */
final class OgImageController extends Controller
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    public function default(Request $request): Response
    {
        $title = (string) $request->query('title', 'Prezenty od serca, bez stresu');
        $subtitle = (string) $request->query('subtitle', 'DajPrezent.pl');

        $png = Cache::remember(
            'og.png:'.hash('sha256', $title.'|'.$subtitle),
            now()->addHours(24),
            fn () => $this->render($title, $subtitle),
        );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400, immutable',
        ]);
    }

    public function forTenant(Tenant $tenant): Response
    {
        $title = $tenant->name;
        $subtitle = 'dajprezent.pl/'.$tenant->slug;

        $png = Cache::remember(
            'og.png.tenant:'.$tenant->id,
            now()->addHours(24),
            fn () => $this->render($title, $subtitle),
        );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400, immutable',
        ]);
    }

    /**
     * 1200x630 z brand gradientem (vertical) + biały headline + niżej muted subtitle
     * + DajPrezent.pl wordmark w lewym dolnym rogu. Czcionka — DejaVu Sans (ships z GD).
     */
    private function render(string $title, string $subtitle): string
    {
        $im = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($im === false) {
            throw new \RuntimeException('GD image alloc failed.');
        }

        // Brand gradient #4F46E5 → #3B82F6 (vertical).
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / self::HEIGHT;
            $r = (int) round(0x4F + ($t * (0x3B - 0x4F)));
            $g = (int) round(0x46 + ($t * (0x82 - 0x46)));
            $b = (int) round(0xE5 + ($t * (0xF6 - 0xE5)));
            imageline($im, 0, $y, self::WIDTH, $y, imagecolorallocate($im, $r, $g, $b));
        }

        // Subtle decorative blobs (soft circles, alpha).
        $blob = imagecolorallocatealpha($im, 255, 255, 255, 110);
        imagefilledellipse($im, 1080, 80, 320, 320, $blob);
        imagefilledellipse($im, 100, 580, 380, 380, $blob);

        $white = imagecolorallocate($im, 255, 255, 255);
        $muted = imagecolorallocate($im, 232, 234, 255);

        // Wordmark "DajPrezent.pl" in bottom-left corner.
        $font = $this->fontPath();
        if ($font !== null) {
            imagettftext($im, 22, 0, 60, self::HEIGHT - 60, $white, $font, 'DajPrezent.pl');

            // Headline — auto-wrap to ≤ 24 chars/line, max 3 lines.
            $lines = $this->wrap($title, 26, 3);
            $y = 220;
            foreach ($lines as $line) {
                imagettftext($im, 56, 0, 60, $y, $white, $font, $line);
                $y += 72;
            }

            // Subtitle.
            imagettftext($im, 28, 0, 60, $y + 20, $muted, $font, $subtitle);

            // Small chip in the top-right.
            imagettftext($im, 18, 0, 880, 80, $muted, $font, '✓ Bez konta dla gości');
        } else {
            imagestring($im, 5, 60, 80, 'DajPrezent.pl', $white);
            imagestring($im, 5, 60, 200, $title, $white);
            imagestring($im, 4, 60, 260, $subtitle, $muted);
        }

        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    /** @return list<string> */
    private function wrap(string $text, int $maxChars, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $w) {
            if (mb_strlen($current) + mb_strlen($w) + 1 <= $maxChars) {
                $current = $current === '' ? $w : $current.' '.$w;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $w;
            }
            if (count($lines) === $maxLines - 1 && mb_strlen($current) > $maxChars) {
                break;
            }
        }
        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        return array_slice($lines, 0, $maxLines);
    }

    private function fontPath(): ?string
    {
        // DejaVu Sans Bold ships with most distros + Composer envs.
        foreach ([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

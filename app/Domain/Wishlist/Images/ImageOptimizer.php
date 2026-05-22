<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * GD-based image normaliser.
 *
 * On upload we:
 *   1. Read the file with imagecreatefromstring (auto-detects JPEG /
 *      PNG / WebP — refuses anything else).
 *   2. If longest edge > MAX_EDGE, resample down proportionally —
 *      no upscaling.
 *   3. Encode as WebP, quality 85. WebP wins on size and is
 *      universally supported by modern browsers (and a server-side
 *      JPEG fallback is unnecessary in 2026).
 *   4. Store under gifts/{tenant_id}/{uuid}.webp on the 'public' disk.
 *
 * Stays library-free on purpose — intervention/image / spatie/image
 * would be overkill for the single use case and would add a Composer
 * dep we don't otherwise need.
 */
final class ImageOptimizer
{
    public const MAX_EDGE = 1200;

    public const WEBP_QUALITY = 85;

    public function storeForTenant(UploadedFile $file, int $tenantId, string $disk = 'public'): string
    {
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false || $raw === '') {
            throw new RuntimeException('Cannot read uploaded image.');
        }

        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            throw new RuntimeException('Uploaded file is not a recognisable image.');
        }

        try {
            $w = imagesx($src);
            $h = imagesy($src);

            $longest = max($w, $h);
            if ($longest > self::MAX_EDGE) {
                $scale = self::MAX_EDGE / $longest;
                $newW = (int) round($w * $scale);
                $newH = (int) round($h * $scale);

                $dst = imagecreatetruecolor($newW, $newH);
                if ($dst === false) {
                    throw new RuntimeException('imagecreatetruecolor failed.');
                }

                // Preserve transparency for PNG/WebP sources.
                imagealphablending($dst, false);
                imagesavealpha($dst, true);

                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }

            ob_start();
            imagewebp($src, null, self::WEBP_QUALITY);
            $webp = ob_get_clean();
            if ($webp === false || $webp === '') {
                throw new RuntimeException('WebP encoding produced empty output.');
            }
        } finally {
            if ($src instanceof \GdImage) {
                @imagedestroy($src);
            }
        }

        $path = sprintf('gifts/%d/%s.webp', $tenantId, Str::random(40));
        if (! Storage::disk($disk)->put($path, $webp)) {
            throw new RuntimeException('Failed to persist optimised image.');
        }

        return $path;
    }
}

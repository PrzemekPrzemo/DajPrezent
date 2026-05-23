<?php

declare(strict_types=1);

namespace App\Domain\Wishlist;

/**
 * Defuse formula-injection in CSV cells.
 *
 * Excel / Sheets / LibreOffice / Numbers all execute formulas when a
 * cell starts with `=`, `+`, `-`, `@`, TAB, or CR. A malicious row
 * imported via GiftImportController could be re-emitted by
 * GiftExportController and detonate on a victim's machine — see
 * CVE-2014-3524 family.
 *
 * Two complementary defences:
 *   - sanitiseImport()  — strip the leading dangerous char on the way IN,
 *                         so the stored value is plain text.
 *   - sanitiseExport()  — prefix any still-dangerous value with a single
 *                         quote on the way OUT, so spreadsheet apps
 *                         treat it as a string.
 *
 * Belt-and-braces: even if old rows slip through the import filter
 * (e.g. created via the bookmarklet before this fix), the export side
 * still protects downstream recipients.
 */
final class CsvCell
{
    private const DANGER_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public static function sanitiseImport(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        // Strip every leading dangerous character (handles "==" / "-=" / "@+").
        $trimmed = $value;
        while ($trimmed !== '' && in_array($trimmed[0], self::DANGER_PREFIXES, true)) {
            $trimmed = substr($trimmed, 1);
        }

        return $trimmed;
    }

    public static function sanitiseExport(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (in_array($value[0], self::DANGER_PREFIXES, true)) {
            return "'".$value;
        }

        return $value;
    }
}

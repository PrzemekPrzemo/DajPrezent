<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wedding\Models\Rsvp;
use App\Domain\Wedding\Models\WeddingEvent;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Three downloads for wedding tenants:
 *   - rsvps.csv   — full RSVP list for the caterer
 *   - invitation.pdf — printable A6 invite with QR
 *   - guests.csv  — just names + plus_one, anonymised emails
 *
 * All gated to wedding_basic / wedding_premium owners.
 */
final class WeddingExportController extends Controller
{
    public function rsvpsCsv(Request $request, Tenant $tenant): StreamedResponse
    {
        $this->authorizeWedding($request, $tenant);

        $rsvps = Rsvp::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get();

        $filename = sprintf('rsvps-%s-%s.csv', $tenant->slug, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($rsvps): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, [
                'Imię i nazwisko', 'E-mail', 'Obecność', 'Osoba towarzysząca',
                'Imię +1', 'Dieta / alergie', 'Transport', 'Wiadomość', 'Zgłoszono',
            ], ';');

            foreach ($rsvps as $r) {
                fputcsv($out, [
                    $r->guest_name,
                    $r->guest_email ?? '',
                    $r->attending ? 'TAK' : 'NIE',
                    $r->plus_one ? 'TAK' : 'NIE',
                    $r->plus_one_name ?? '',
                    $r->dietary ?? '',
                    $r->transport_needed ? 'TAK' : 'NIE',
                    $r->message ?? '',
                    $r->created_at?->format('Y-m-d H:i') ?? '',
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function invitationPdf(Request $request, Tenant $tenant): Response
    {
        $this->authorizeWedding($request, $tenant);

        $event = WeddingEvent::query()->where('tenant_id', $tenant->id)->first();
        $publicUrl = url('/'.$tenant->slug);

        $pdf = Pdf::loadView('owner.wedding.pdf.invitation', [
            'tenant' => $tenant,
            'event' => $event,
            'publicUrl' => $publicUrl,
            // QR jako data URI — SvgWriter zwraca XML, dompdf nie radzi
            // sobie ze SVG dobrze. Używamy PNG.
            'qrDataUri' => $this->buildQrDataUri($publicUrl),
        ])->setPaper([0, 0, 297.6, 419.5], 'portrait'); // A6 in points

        return $pdf->download(sprintf('zaproszenie-%s.pdf', $tenant->slug));
    }

    private function buildQrDataUri(string $url): string
    {
        $builder = new Builder(
            writer: new PngWriter,
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 4,
            foregroundColor: new Color(30, 41, 59),  // dp-navy
            backgroundColor: new Color(255, 255, 255),
        );

        return $builder->build()->getDataUri();
    }

    private function authorizeWedding(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
        if (! in_array($tenant->kind, ['wedding_basic', 'wedding_premium'], true)) {
            abort(404);
        }
    }
}

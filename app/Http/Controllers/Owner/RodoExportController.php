<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\TenantDataExporter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RODO art. 20 — right to data portability. Streams a JSON dump of
 * the user's account + tenants + gifts + invoices.
 *
 * Guest e-mails on gift_reservations are intentionally excluded —
 * the list owner is not the data controller for those.
 */
final class RodoExportController extends Controller
{
    public function __construct(private readonly TenantDataExporter $exporter) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $payload = $this->exporter->export($user);

        $filename = sprintf('dajprezent-rodo-export-%d-%s.json', $user->id, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SVG QR of the public list URL. Owners drop it on the dashboard
 * share widget or download for physical print (invitations, table
 * cards). Brand purple #4F46E5 instead of pure black.
 */
final class TenantQrController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }

        $builder = new Builder(
            writer: new SvgWriter,
            data: url('/'.$tenant->slug),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 360,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(79, 70, 229),   // dp-purple-600
            backgroundColor: new Color(255, 255, 255),
        );

        $svg = $builder->build()->getString();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }
}

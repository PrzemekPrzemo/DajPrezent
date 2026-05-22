<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Billing\Models\Package;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class PricingController extends Controller
{
    public function __invoke(): View
    {
        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('price_pln_gr')
            ->get()
            ->groupBy('kind');

        return view('public.pricing', ['packages' => $packages]);
    }
}

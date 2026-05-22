<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Billing\Checkout\CheckoutOrderData;
use App\Domain\Billing\Checkout\CheckoutService;
use App\Domain\Billing\Models\Package;
use App\Domain\Tenancy\Rules\AllowedSlug;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    public function buy(Request $request, string $code): View|RedirectResponse
    {
        $package = $this->findActivePackage($code);

        if ($request->user() === null) {
            return redirect()->route('register', ['package' => $code]);
        }

        return view('public.checkout.buy', [
            'package' => $package,
        ]);
    }

    public function store(Request $request, string $code): RedirectResponse
    {
        $package = $this->findActivePackage($code);
        $user = $request->user();
        abort_if($user === null, 401);

        $data = $request->validate([
            'slug' => ['required', 'string', new AllowedSlug, Rule::unique('tenants', 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'locale' => ['required', 'in:pl,en'],
            'terms' => ['accepted'],
        ]);

        $result = $this->checkout->start(
            buyer: $user,
            package: $package,
            data: new CheckoutOrderData(
                slug: $data['slug'],
                tenantName: $data['name'],
                locale: $data['locale'],
                customerIp: (string) $request->ip(),
            ),
        );

        if ($result->redirectUri !== null) {
            // Out-of-app to PayU checkout.
            return redirect()->away($result->redirectUri);
        }

        // Free plan: skip PayU, go straight to the manage page.
        return redirect()
            ->route('owner.gifts.index', $result->tenant)
            ->with('status', 'Twoja lista jest gotowa. Dodaj pierwszy prezent!');
    }

    public function return(Request $request): View
    {
        // PayU sends `?error=...` if the user cancelled. The actual
        // activation happens via the IPN webhook; this view just tells
        // the buyer "we got it, refresh in a moment".
        return view('public.checkout.return', [
            'cancelled' => $request->query('error') === 'CANCELED' || $request->has('error'),
        ]);
    }

    private function findActivePackage(string $code): Package
    {
        $package = Package::query()->where('code', $code)->where('is_active', true)->first();
        if ($package === null) {
            throw new NotFoundHttpException;
        }

        return $package;
    }
}

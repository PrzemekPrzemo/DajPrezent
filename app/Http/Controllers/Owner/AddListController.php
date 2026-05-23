<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\AddSiblingListService;
use App\Domain\Tenancy\Rules\AllowedSlug;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Owner spawns a second (or third…) list under their already-paid pakiet.
 *
 * Eligibility check (AddSiblingListService) decides whether the user has
 * any subscription with a free `multiple_lists` slot. If not, redirect
 * back with an error pointing to /pakiety.
 */
final class AddListController extends Controller
{
    public function __construct(private readonly AddSiblingListService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $parent = $this->service->eligibleSubscription($user);
        if ($parent === null) {
            return back()->withErrors([
                'limit' => 'Twoje pakiety nie pozwalają na dodatkową listę. Kup pakiet Plus lub Pro, aby mieć kilka list.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', Rule::unique('tenants', 'slug'), new AllowedSlug],
        ]);

        $tenant = $this->service->create($user, $parent, $data['slug'], $data['name']);

        return redirect()
            ->route('owner.gifts.index', $tenant)
            ->with('status', 'Dodano kolejną listę: '.$tenant->name);
    }
}

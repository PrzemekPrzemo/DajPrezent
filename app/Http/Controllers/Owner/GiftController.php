<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\GiftLimitGuard;
use App\Domain\Wishlist\Images\ImageOptimizer;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class GiftController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $current,
        private readonly ImageOptimizer $optimizer,
        private readonly GiftLimitGuard $limit,
    ) {}

    public function index(Request $request, Tenant $tenant): View
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $gifts = Gift::query()
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        return view('owner.gifts.index', [
            'tenant' => $tenant,
            'gifts' => $gifts,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $data = $this->validateForm($request);

        if (! $this->limit->canAdd($tenant)) {
            return back()->withErrors(['limit' => $this->limit->errorFor($tenant)])->withInput();
        }

        $isFirstGift = Gift::query()->count() === 0;

        Gift::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'price_pln_gr' => isset($data['price_pln']) ? (int) round((float) $data['price_pln'] * 100) : null,
            'priority' => (int) $data['priority'],
            'status' => Gift::STATUS_AVAILABLE,
            'position' => Gift::query()->max('position') + 1,
            'image_path' => $this->storeImageIfPresent($request, $tenant),
        ]);

        $redirect = redirect()
            ->route('owner.gifts.index', $tenant)
            ->with('status', 'Prezent dodany do listy.');

        // Tiny dopamine hit: confetti only on the first gift on this list.
        return $isFirstGift ? $redirect->with('dp_confetti', 'first-gift') : $redirect;
    }

    public function update(Request $request, Tenant $tenant, int $gift): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $giftModel = Gift::query()->findOrFail($gift);
        $data = $this->validateForm($request);

        $patch = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'price_pln_gr' => isset($data['price_pln']) ? (int) round((float) $data['price_pln'] * 100) : null,
            'priority' => (int) $data['priority'],
        ];

        $newImage = $this->storeImageIfPresent($request, $tenant);
        if ($newImage !== null) {
            $this->deleteImage($giftModel->image_path);
            $patch['image_path'] = $newImage;
        }

        if ($request->boolean('remove_image') && $giftModel->image_path !== null) {
            $this->deleteImage($giftModel->image_path);
            $patch['image_path'] = null;
        }

        $giftModel->update($patch);

        return back()->with('status', 'Zapisano zmiany.');
    }

    public function destroy(Request $request, Tenant $tenant, int $gift): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $giftModel = Gift::query()->findOrFail($gift);
        $this->deleteImage($giftModel->image_path);
        $giftModel->delete();

        return back()->with('status', 'Prezent usunięty.');
    }

    /**
     * @return array{title: string, description?: ?string, url?: ?string, price_pln?: ?string, priority: int|string}
     */
    private function validateForm(Request $request): array
    {
        /** @var array{title: string, description?: ?string, url?: ?string, price_pln?: ?string, priority: int|string} $data */
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'max:1024'],
            'price_pln' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'priority' => ['required', 'integer', 'between:1,3'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'], // 4 MB
        ]);

        return $data;
    }

    private function storeImageIfPresent(Request $request, Tenant $tenant): ?string
    {
        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return null;
        }

        // ImageOptimizer downsizes to 1200px longest edge and re-encodes
        // as WebP at quality 85; path is tenant-namespaced so a stray
        // Storage::deleteDirectory only affects this tenant.
        return $this->optimizer->storeForTenant($file, $tenant->id, $this->disk());
    }

    private function deleteImage(?string $path): void
    {
        if ($path === null) {
            return;
        }
        Storage::disk($this->disk())->delete($path);
    }

    private function disk(): string
    {
        // 'public' is symlinked from public/storage by `artisan storage:link`
        // so the existing `asset('storage/'.$path)` in views resolves to a
        // real URL. Production can override to s3 via filesystems.disks.
        return 'public';
    }

    public function markReceived(Request $request, Tenant $tenant, int $gift): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $giftModel = Gift::query()->findOrFail($gift);
        $giftModel->update(['status' => Gift::STATUS_RECEIVED]);

        return back()
            ->with('status', 'Oznaczono prezent jako otrzymany.')
            ->with('dp_heart', $giftModel->id);
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
    }
}

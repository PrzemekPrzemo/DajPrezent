<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $package_id
 * @property string $status
 * @property int $amount_pln_gr
 * @property ?string $buyer_name
 * @property ?string $buyer_company
 * @property ?string $buyer_nip
 * @property ?string $buyer_street
 * @property ?string $buyer_postal_code
 * @property ?string $buyer_city
 * @property string $buyer_country
 * @property ?string $payu_order_id
 * @property ?Carbon $paid_at
 * @property ?Carbon $expires_at
 * @property ?int $invoice_id
 */
final class Subscription extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'package_id', 'status', 'amount_pln_gr',
        'buyer_name', 'buyer_company', 'buyer_nip',
        'buyer_street', 'buyer_postal_code', 'buyer_city', 'buyer_country',
        'payu_order_id', 'paid_at', 'expires_at', 'invoice_id',
    ];

    public function isB2B(): bool
    {
        return $this->buyer_nip !== null && $this->buyer_nip !== '';
    }

    public function buyerDisplayName(): string
    {
        return $this->isB2B() && $this->buyer_company !== null && $this->buyer_company !== ''
            ? $this->buyer_company
            : ($this->buyer_name ?? '');
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}

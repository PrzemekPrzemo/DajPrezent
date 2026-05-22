<?php

declare(strict_types=1);

namespace App\Domain\Invoicing\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $number
 * @property string $buyer_name
 * @property ?string $buyer_nip
 * @property array $buyer_address
 * @property array $items
 * @property int $total_net_gr
 * @property int $total_vat_gr
 * @property int $total_gross_gr
 * @property string $status
 * @property ?string $ksef_reference_number
 * @property ?Carbon $ksef_acquisition_at
 * @property ?string $pdf_path
 */
final class Invoice extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'number', 'buyer_name', 'buyer_nip', 'buyer_address',
        'items', 'total_net_gr', 'total_vat_gr', 'total_gross_gr',
        'status', 'ksef_reference_number', 'ksef_acquisition_at', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'buyer_address' => 'array',
            'items' => 'array',
            'ksef_acquisition_at' => 'datetime',
        ];
    }
}

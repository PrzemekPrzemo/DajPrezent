<?php

declare(strict_types=1);

namespace App\Domain\Wedding\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Guest RSVP response. PII includes guest_email — hidden from the
 * owner's view by default (toArray omits it). Owner sees aggregated
 * counts and per-row details only with explicit "show contact" UI.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $guest_name
 * @property ?string $guest_email
 * @property bool $attending
 * @property bool $plus_one
 * @property ?string $plus_one_name
 * @property ?string $dietary
 * @property bool $transport_needed
 * @property ?string $message
 * @property ?string $ip
 */
final class Rsvp extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'guest_name', 'guest_email',
        'attending', 'plus_one', 'plus_one_name',
        'dietary', 'transport_needed', 'message', 'ip',
    ];

    protected $hidden = ['guest_email', 'ip'];

    protected function casts(): array
    {
        return [
            'attending' => 'bool',
            'plus_one' => 'bool',
            'transport_needed' => 'bool',
        ];
    }

    /**
     * Total seats reserved (guest + plus_one).
     */
    public function headCount(): int
    {
        return $this->attending ? (1 + ($this->plus_one ? 1 : 0)) : 0;
    }
}

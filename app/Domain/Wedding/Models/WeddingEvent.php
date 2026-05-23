<?php

declare(strict_types=1);

namespace App\Domain\Wedding\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One-to-one with Tenant for wedding tiers (kind = wedding_basic /
 * wedding_premium). Carries everything a wedding micro-site needs:
 * couple, ceremony location + time, schedule, story, accommodation,
 * RSVP deadline, chosen theme.
 *
 * @property int $id
 * @property int $tenant_id
 * @property ?string $couple_names
 * @property ?string $hashtag
 * @property ?Carbon $ceremony_at
 * @property ?string $venue_name
 * @property ?string $venue_address
 * @property ?float $venue_lat
 * @property ?float $venue_lng
 * @property ?string $reception_venue_name
 * @property ?string $reception_venue_address
 * @property ?string $dress_code
 * @property ?string $story_text
 * @property ?string $schedule_text
 * @property ?string $accommodation_text
 * @property ?Carbon $rsvp_deadline
 * @property string $theme
 */
final class WeddingEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const THEMES = ['classic', 'minimalist', 'garden', 'gold'];

    protected $fillable = [
        'tenant_id', 'couple_names', 'hashtag', 'ceremony_at',
        'venue_name', 'venue_address', 'venue_lat', 'venue_lng',
        'reception_venue_name', 'reception_venue_address',
        'dress_code', 'story_text', 'schedule_text', 'accommodation_text',
        'rsvp_deadline', 'theme',
    ];

    protected function casts(): array
    {
        return [
            'ceremony_at' => 'datetime',
            'rsvp_deadline' => 'date',
            'venue_lat' => 'decimal:6',
            'venue_lng' => 'decimal:6',
        ];
    }

    public function tenantRelation(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

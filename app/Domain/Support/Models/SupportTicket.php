<?php

declare(strict_types=1);

namespace App\Domain\Support\Models;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?int $user_id
 * @property ?int $tenant_id
 * @property string $category
 * @property string $priority
 * @property string $subject
 * @property string $body
 * @property ?string $contact_email
 * @property string $status
 * @property ?string $admin_notes
 * @property ?Carbon $resolved_at
 * @property ?string $ip
 */
final class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORIES = ['billing', 'technical', 'rodo', 'other'];

    public const PRIORITIES = ['low', 'normal', 'high'];

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    protected $fillable = [
        'user_id', 'tenant_id', 'category', 'priority',
        'subject', 'body', 'contact_email', 'status',
        'admin_notes', 'resolved_at', 'ip',
    ];

    protected $hidden = ['ip', 'admin_notes'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

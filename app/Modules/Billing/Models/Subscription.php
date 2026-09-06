<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Subscription extends Model
{
    use HasUuids;
    use BelongsToTenant;

    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id', 'tier', 'seats_licensed', 'billing_cycle', 'status',
        'current_period_start', 'current_period_end',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
    ];

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }
}

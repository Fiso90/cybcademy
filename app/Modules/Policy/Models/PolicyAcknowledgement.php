<?php

declare(strict_types=1);

namespace App\Modules\Policy\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable per BR-3 (Phase 2 SRS Section 6). This model intentionally:
 *   - has no `updated_at` (`public $timestamps = false`, `created_at` set
 *     explicitly on create)
 *   - is never the target of an ->update() call anywhere in the codebase
 *     (enforced by convention + code review, not a framework guarantee -
 *     see PolicyAcknowledgementService for the one legitimate write path)
 *
 * A "correction" is a new row against a new Policy version, never an edit
 * of an existing row - the unique constraint on
 * (tenant_id, policy_id, user_id) in the migration makes a second
 * acknowledgement of the *same* policy version impossible by construction,
 * which is what forces corrections through the "new version" path rather
 * than a re-acknowledgement of the same version.
 */
final class PolicyAcknowledgement extends Model
{
    use HasUuids;
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'policy_acknowledgements';

    protected $fillable = ['tenant_id', 'policy_id', 'user_id', 'acknowledged_at', 'ip_address', 'created_at'];

    protected $casts = ['acknowledged_at' => 'datetime'];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Employee\Events;

use App\Modules\Employee\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an employee is deactivated/offboarded. Listened to by
 * revocation handlers across modules (e.g. revoking outstanding API
 * tokens, forcibly ending active sessions per Phase 7 Section 3) so that
 * offboarding is a single, reliable trigger point rather than something
 * each module has to be separately reminded to hook into.
 */
final class EmployeeOffboarded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $employee,
    ) {
    }
}

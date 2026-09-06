<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Modules\Employee\Models\User;
use App\Modules\Notifications\Models\Notification;
use App\Modules\Policy\Services\PolicyService;
use App\Support\TenantContext;

/**
 * FR-10.5. Also the concrete consumer of
 * PolicyService::usersWithOutstandingAcknowledgement() (Epic E3), which
 * that module's README flagged as "a pure query method with no scheduled
 * dispatch yet... belongs to the Notifications module consuming this
 * method" - this class is where that connection actually gets made.
 */
final class NotificationService
{
    public function __construct(
        private readonly PolicyService $policyService,
    ) {
    }

    public function notify(User $user, string $type, array $payload): Notification
    {
        return Notification::create([
            'tenant_id' => TenantContext::current(),
            'user_id' => $user->id,
            'type' => $type,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    public function markRead(string $notificationId, User $user): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $user->id) // a user can only mark their own notifications read
            ->update(['read_at' => now()]);
    }

    /**
     * FR-4.3's reminder dispatch, closing the loop left open in Epic E3.
     * Scheduled invocation (e.g. weekly) wired up in Phase 13, same as
     * Epic E6's RecalculateHumanRiskScores job.
     */
    public function sendOutstandingPolicyReminders(string $policyTitle): int
    {
        $usersOwed = $this->policyService->usersWithOutstandingAcknowledgement($policyTitle);

        foreach ($usersOwed as $user) {
            $this->notify($user, 'policy_acknowledgement_reminder', [
                'policy_title' => $policyTitle,
            ]);
        }

        return $usersOwed->count();
    }
}

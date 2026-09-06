<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Repositories;

use App\Modules\Employee\Models\User;
use App\Modules\PhishingSimulation\Models\PhishingCampaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The single enforcement point for BR-4 (Phase 2 SRS Section 6):
 * "Phishing simulation results shall never be exposed to an employee's
 * direct manager in a way that identifies individual failure without
 * organisation-level opt-in configuration - default is aggregate/
 * department-level visibility only."
 *
 * This is deliberately the ONLY place in the codebase that queries
 * phishing_results for reporting purposes. Every caller - the API
 * controller, the Executive Dashboard (Epic E6), any future export - goes
 * through one of these two methods, never a raw query against the model.
 * That means the access-control decision (individual vs. aggregate) is
 * made in exactly one place and is not something each new consumer of
 * this data could independently get wrong.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 10 (Risks)
 */
final class PhishingResultsRepository
{
    /**
     * Individual-level results. Callers MUST verify the requesting user
     * holds Security Officer, Compliance Officer, or Admin privilege
     * BEFORE calling this method - this method itself does not check
     * authorization, by design, so that the authorization decision stays
     * visible at the call site (see PhishingCampaignController) rather
     * than being silently baked into a repository method a future
     * developer might call from a different, less-privileged context
     * without realising what they were exposing.
     */
    public function individualResults(PhishingCampaign $campaign): Collection
    {
        return $campaign->results()
            ->with('user:id,name,department_id')
            ->orderBy('event_at')
            ->get();
    }

    /**
     * Department-aggregated results - the DEFAULT view per BR-4, safe to
     * expose to Manager-level roles without any additional authorization
     * check, since no row here can be traced back to a specific
     * individual's outcome.
     */
    public function aggregatedByDepartment(PhishingCampaign $campaign): Collection
    {
        return DB::table('phishing_results')
            ->join('users', 'users.id', '=', 'phishing_results.user_id')
            ->where('phishing_results.campaign_id', $campaign->id)
            ->select([
                'users.department_id',
                'phishing_results.event_type',
                DB::raw('count(*) as event_count'),
            ])
            ->groupBy('users.department_id', 'phishing_results.event_type')
            ->get();
    }

    /**
     * Returns whichever view is appropriate for the requesting user's
     * role - the one method most controllers should actually call, since
     * it makes "you get individual data only if you're privileged enough"
     * impossible to bypass by calling the wrong method.
     */
    public function resultsForRequester(PhishingCampaign $campaign, User $requester): Collection
    {
        $privilegedRoles = ['security-officer', 'compliance-officer', 'org-admin', 'sys-admin'];

        $canSeeIndividualResults = collect($privilegedRoles)
            ->contains(fn (string $slug) => $requester->hasRole($slug));

        return $canSeeIndividualResults
            ? $this->individualResults($campaign)
            : $this->aggregatedByDepartment($campaign);
    }
}

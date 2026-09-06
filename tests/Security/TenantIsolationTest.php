<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Automated tenant-isolation leak-detection test.
 *
 * This is the test referenced repeatedly across the design documents as a
 * required, non-optional safeguard:
 *   - Phase 5 Section 6 (RLS "opt-out, not opt-in" pattern)
 *   - Phase 5 Risks (RLS policy coverage verification)
 *   - Phase 7 Section 2 A01 (automated tenant-isolation tests)
 *   - Phase 9 Risks (E1 foundation risk item)
 *
 * It asserts BOTH layers of the two-layer isolation design independently:
 * the application-layer global scope, and the database-layer RLS policy,
 * so that a regression in either layer is caught even if the other layer
 * happens to still be masking the problem.
 *
 * IMPORTANT CAVEAT, discovered via live verification against a real
 * PostgreSQL instance outside this test suite (see
 * README_LIVE_VERIFICATION_REPORT.md): RefreshDatabase wraps each test
 * in one real database transaction, which incidentally means a bug in
 * TenantContext::set()'s use of set_config's is_local parameter (it
 * originally used is_local => true, which only persists for the current
 * transaction) went undetected here even though it would have broken
 * the application under real PHP-FPM request handling, where no such
 * enclosing transaction exists. This suite is necessary but was not, on
 * its own, sufficient to catch that specific class of bug - worth
 * remembering when adding future tests that touch session-scoped
 * PostgreSQL state.
 */
final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_layer_scope_prevents_cross_tenant_reads(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userInTenantA = User::factory()->for($tenantA)->create();
        User::factory()->for($tenantB)->create();

        TenantContext::set($tenantA->id);

        $visibleUsers = User::all();

        $this->assertCount(1, $visibleUsers);
        $this->assertTrue($visibleUsers->first()->is($userInTenantA));
    }

    public function test_database_layer_rls_prevents_cross_tenant_reads_even_without_the_eloquent_scope(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        User::factory()->for($tenantA)->create();
        User::factory()->for($tenantB)->create();

        TenantContext::set($tenantA->id);

        // Deliberately bypass the application-layer scope to prove RLS
        // alone still enforces the boundary - this is the "what if a
        // developer forgets the trait" scenario the two-layer design
        // exists to protect against.
        $rawRows = User::withoutTenantScope()->get();

        $this->assertCount(
            1,
            $rawRows,
            'Row-Level Security did not restrict results to the current '
            . 'tenant even with the application-layer scope bypassed. '
            . 'This indicates an RLS policy is missing or misconfigured '
            . 'for the users table.'
        );
    }

    /**
     * @dataProvider tenantScopedTables
     */
    public function test_every_tenant_scoped_table_has_an_rls_policy(string $table): void
    {
        $policyExists = DB::table('pg_policies')
            ->where('tablename', $table)
            ->exists();

        $this->assertTrue(
            $policyExists,
            "Table '{$table}' has a tenant_id column but no PostgreSQL RLS "
            . 'policy was found. Per Phase 5 Section 6, every tenant-scoped '
            . 'table must have RLS enabled in the same migration that '
            . 'creates it.'
        );
    }

    public static function tenantScopedTables(): array
    {
        // Extend this list as each module's migrations land in Phase 10 -
        // deliberately hand-maintained rather than introspected from the
        // schema, so that adding a new tenant_id column without adding it
        // here is itself a visible gap in code review.
        return [
            ['departments'],
            ['users'],
            ['roles'],
            ['model_has_roles'],
            ['audit_logs'],
            // Added in Epic E2 (Phase 10 code drop 2):
            ['courses'],
            ['lessons'],
            ['question_bank'],
            ['assessments'],
            ['assessment_attempts'],
            ['course_assignments'],
            ['certificates'],
            // Added in Epics E3/E5 (Phase 10 code drop 3):
            ['policies'],
            ['policy_acknowledgements'],
            ['incidents'],
            // Added in Epic E4 (Phase 10 code drop 4). Note:
            // phishing_templates deliberately has its own custom policy
            // (see migration 2026_01_04_000001) rather than the standard
            // TenantRls helper, since it must remain readable across
            // tenants for tenant_id IS NULL platform templates - it still
            // appears in pg_policies, just with different USING logic, so
            // this coverage check still correctly confirms a policy
            // exists on it.
            ['phishing_templates'],
            ['phishing_campaigns'],
            ['phishing_results'],
            // Added in Epic E6 (Phase 10 code drop 5):
            ['human_risk_scores'],
            // Added in Epic E7 (Phase 10 code drop 6):
            ['audit_exports'],
            // Epic E9 (AI Features) introduces no new tenant-scoped
            // tables of its own - generated content lands in existing
            // tables (question_bank, phishing_templates) already covered
            // above.
            // Added in Epic E10 (Phase 10 code drop 8):
            ['subscriptions'],
            ['notifications'],
            ['knowledge_base_articles'], // custom asymmetric policy, same shape as phishing_templates - see migration docblock
        ];
    }
}

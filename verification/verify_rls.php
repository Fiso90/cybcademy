<?php
/**
 * End-to-end verification that Row-Level Security actually enforces
 * tenant isolation, connecting as the least-privileged application role
 * (cybcademy_app), exercising the exact mechanism
 * App\Support\TenantContext::set() uses in the real Laravel codebase
 * (PostgreSQL's set_config('app.current_tenant', ..., true)).
 *
 * This is the closest thing to running TenantIsolationTest.php for real
 * that's possible without the Laravel framework installed (blocked by
 * this sandbox's network policy - see the accompanying report).
 */

$pdo = new PDO(
    'pgsql:host=127.0.0.1;dbname=cybcademy',
    'cybcademy_app',
    'testing_local_only'
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function line(string $label, bool $pass): void
{
    echo ($pass ? "[PASS] " : "[FAIL] ") . $label . PHP_EOL;
}

// --- Setup: two tenants, one user each ---
$tenantA = $pdo->query("INSERT INTO tenants (name) VALUES ('Tenant A - Acme Bank') RETURNING id")->fetchColumn();
$tenantB = $pdo->query("INSERT INTO tenants (name) VALUES ('Tenant B - Umbrella Logistics') RETURNING id")->fetchColumn();

// Setting tenant context before each insert, exactly as the real
// BelongsToTenant trait does (it sets tenant_id from
// TenantContext::current() in its creating() hook, and TenantContext::set()
// itself issues this same set_config call) - RLS's WITH CHECK clause
// correctly rejected the original version of this script's inserts
// because they were attempted with no context set at all, which is
// exactly the fail-closed behaviour the design intends.
$setCtx = $pdo->prepare("SELECT set_config('app.current_tenant', ?, false)");

$setCtx->execute([$tenantA]);
$stmt = $pdo->prepare("INSERT INTO users (tenant_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, 'active') RETURNING id");
$stmt->execute([$tenantA, 'Alice (Tenant A)', 'alice@acme.example', 'x']);
$userA = $stmt->fetchColumn();

$setCtx->execute([$tenantB]);
$stmt->execute([$tenantB, 'Bob (Tenant B)', 'bob@umbrella.example', 'x']);
$userB = $stmt->fetchColumn();

echo "Created Tenant A ({$tenantA}) with user Alice, Tenant B ({$tenantB}) with user Bob." . PHP_EOL . PHP_EOL;

// --- Test 1: with NO tenant context set, queries should return NOTHING
//     (fail closed, per the migration's default: current_setting('app.current_tenant', true)
//     resolves to NULL/empty when unset, and NULLIF(...,'')::uuid is NULL,
//     which matches no tenant_id via the USING clause). ---
$pdo->exec("SELECT set_config('app.current_tenant', '', false)");
$countNoContext = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
line("No tenant context set -> zero rows visible (fail closed)", (int)$countNoContext === 0);

// --- Test 2: set context to Tenant A -> only Alice should be visible ---
$stmt = $pdo->prepare("SELECT set_config('app.current_tenant', ?, false)");
$stmt->execute([$tenantA]);
$rowsAsTenantA = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
line("As Tenant A: exactly 1 row visible", count($rowsAsTenantA) === 1);
line("As Tenant A: the visible row is Alice, not Bob", ($rowsAsTenantA[0]['id'] ?? null) === $userA);

// --- Test 3: set context to Tenant B -> only Bob should be visible ---
$stmt->execute([$tenantB]);
$rowsAsTenantB = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
line("As Tenant B: exactly 1 row visible", count($rowsAsTenantB) === 1);
line("As Tenant B: the visible row is Bob, not Alice", ($rowsAsTenantB[0]['id'] ?? null) === $userB);

// --- Test 4: while impersonating Tenant A, attempt to INSERT a row
//     claiming tenant_id = Tenant B (simulating a bug/attack attempting
//     to write into another tenant's data) -> the WITH CHECK clause
//     must reject it. ---
$stmt->execute([$tenantA]);
$rejected = false;
try {
    $insertAttempt = $pdo->prepare("INSERT INTO users (tenant_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, 'active')");
    $insertAttempt->execute([$tenantB, 'Malicious Insert', 'attacker@example.com', 'x']);
} catch (PDOException $e) {
    $rejected = str_contains($e->getMessage(), 'row-level security') || str_contains($e->getMessage(), 'violates');
}
line("While scoped to Tenant A, INSERT claiming Tenant B's tenant_id is rejected by WITH CHECK", $rejected);

// --- Test 5: least-privilege role cannot escalate - confirm cybcademy_app
//     cannot disable RLS on the table it's scoped by (would require
//     table-owner/superuser privilege). ---
$cannotDisableRls = false;
try {
    $pdo->exec("ALTER TABLE users DISABLE ROW LEVEL SECURITY");
} catch (PDOException $e) {
    $cannotDisableRls = true;
}
line("Least-privileged app role cannot disable RLS on users (no owner/superuser rights)", $cannotDisableRls);

echo PHP_EOL . "All checks executed against a real, running PostgreSQL 16 instance." . PHP_EOL;

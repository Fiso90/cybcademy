<?php
$pdoApp = new PDO('pgsql:host=127.0.0.1;dbname=cybcademy', 'cybcademy_app', 'testing_local_only');
$pdoApp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function line(string $label, bool $pass): void {
    echo ($pass ? "[PASS] " : "[FAIL] ") . $label . PHP_EOL;
}

$tenantA = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
$pdoApp->prepare("SELECT set_config('app.current_tenant', ?, false)")->execute([$tenantA]);
$pdoApp->prepare("INSERT INTO test_phishing_templates (tenant_id, subject) VALUES (?, 'Tenant A Custom Template')")->execute([$tenantA]);

$visible = $pdoApp->query("SELECT subject FROM test_phishing_templates ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);
line("Tenant A sees both platform template and its own custom template", count($visible) === 2);

$rejected = false;
try {
    $pdoApp->prepare("INSERT INTO test_phishing_templates (tenant_id, subject) VALUES (NULL, 'Attempted platform template from tenant')")->execute();
} catch (PDOException $e) {
    $rejected = true;
}
line("Ordinary tenant cannot author a platform-wide (tenant_id NULL) template", $rejected);

$tenantB = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
$pdoApp->prepare("SELECT set_config('app.current_tenant', ?, false)")->execute([$tenantB]);
$visibleToB = $pdoApp->query("SELECT subject FROM test_phishing_templates")->fetchAll(PDO::FETCH_COLUMN);
line("Tenant B sees only the platform template, not Tenant A's custom one", $visibleToB === ['Platform Template']);

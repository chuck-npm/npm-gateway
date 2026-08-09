<?php
declare(strict_types=1);

use NpmGateway\Container\ServiceProvider;
use NpmGateway\Contracts\{ClockInterface, InitializationTransactionInterface};
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyForbiddenException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Notifications\SupplyOrderEmailSender;
use NpmGateway\Repositories\{PropertyAccessRepository, SupplyOrderRepository};
use NpmGateway\Services\{AuditService, CommunityActionContextResolver, SupplyOrderService, SupplyOrderValidator};
use NpmGateway\Support\{PublicIdGenerator, SupplyOrderPreviewFormatter};
use NpmGateway\ValueObjects\AuthenticatedUser;

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
foreach (['application', 'migration'] as $profile) {
    if (DatabaseProfiles::load($profile, $app['root'])['database'] !== 'npmgateway_test') exit(2);
}
$container = ServiceProvider::build($app);
$db = $container->get(mysqli::class);
$ids = new PublicIdGenerator();
$cleanup = static function (mysqli $db): void {
    $users = array_map('intval', array_column($db->query("SELECT id FROM users WHERE username LIKE 'supplycert%'")->fetch_all(MYSQLI_ASSOC), 'id'));
    $employees = array_map('intval', array_column($db->query("SELECT id FROM employees WHERE employee_number LIKE 'NPM9780%'")->fetch_all(MYSQLI_ASSOC), 'id'));
    $properties = array_map('intval', array_column($db->query("SELECT id FROM properties WHERE slug LIKE 'supply-cert-%'")->fetch_all(MYSQLI_ASSOC), 'id'));
    $userIds = $users ? implode(',', $users) : '0';
    if ($properties) $db->query('DELETE FROM supply_orders WHERE property_id IN ('.implode(',', $properties).')');
    $db->query("DELETE FROM audit_logs WHERE user_id IN ({$userIds})");
    $db->query("DELETE FROM user_property_access WHERE user_id IN ({$userIds})");
    if ($properties) $db->query('DELETE FROM properties WHERE id IN ('.implode(',', $properties).')');
    $db->query("DELETE FROM users WHERE id IN ({$userIds})");
    if ($employees) $db->query('DELETE FROM employees WHERE id IN ('.implode(',', $employees).')');
};
$property = static function (string $slug, string $code, int $propertyNumber) use ($db, $ids): array {
    $publicId = $ids->generate();
    $name = $code.' Supply Certification';
    $managerEmail = $slug.'@example.test';
    $statement = $db->prepare("INSERT INTO properties(public_id,prop_id,property_code,slug,display_name,status,manager_email,address_line_1,city,state,postal_code,timezone) VALUES(?,?,?,?,?,'active',?,'1 Test Way','Scranton','PA','18503','America/New_York')");
    $statement->bind_param('sissss', $publicId, $propertyNumber, $code, $slug, $name, $managerEmail);
    $statement->execute();
    return ['id' => $db->insert_id, 'public_id' => $publicId, 'slug' => $slug];
};

try {
    $cleanup($db);
    $employeePublicId = $ids->generate();
    $statement = $db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES(?,'NPM978001','manager','Supply','Certification','Manager','active','2026-08-09')");
    $statement->bind_param('s', $employeePublicId);
    $statement->execute();
    $employeeId = $db->insert_id;
    $userPublicId = $ids->generate();
    $username = 'supplycertmanager';
    $hash = password_hash('Disposable-123!', PASSWORD_DEFAULT);
    $statement = $db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES(?,?,?,?,'active')");
    $statement->bind_param('siss', $userPublicId, $employeeId, $username, $hash);
    $statement->execute();
    $user = new AuthenticatedUser($db->insert_id, $employeeId, $userPublicId, $employeePublicId, $username, 'Supply Certification');
    $unauthorizedEmployeePublicId = $ids->generate();
    $statement = $db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES(?,'NPM978002','manager','Unauthorized','Certification','Manager','active','2026-08-09')");
    $statement->bind_param('s', $unauthorizedEmployeePublicId);
    $statement->execute();
    $unauthorizedEmployeeId = $db->insert_id;
    $unauthorizedUserPublicId = $ids->generate();
    $unauthorizedUsername = 'supplycertunauthorized';
    $statement = $db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES(?,?,?,?,'active')");
    $statement->bind_param('siss', $unauthorizedUserPublicId, $unauthorizedEmployeeId, $unauthorizedUsername, $hash);
    $statement->execute();
    $unauthorized = new AuthenticatedUser($db->insert_id, $unauthorizedEmployeeId, $unauthorizedUserPublicId, $unauthorizedEmployeePublicId, $unauthorizedUsername, 'Unauthorized Certification');
    $pine = $property('supply-cert-pine', 'SP', 978001);
    $oak = $property('supply-cert-oak', 'SO', 978002);
    $access = new PropertyAccessRepository($db);
    foreach ([$pine, $oak] as $item) $access->grant(['public_id' => $ids->generate(), 'user_id' => $user->id, 'property_id' => $item['id'], 'granted_by_user_id' => $user->id, 'granted_at' => '2026-08-09 09:00:00']);
    $resolver = $container->get(CommunityActionContextResolver::class);
    $request = new AuthenticatedRequestContext($user, 'token');
    $pineContext = $resolver->resolve($request, $pine['slug']);
    $oakContext = $resolver->resolve($request, $oak['slug']);
    try { $resolver->resolve(new AuthenticatedRequestContext($unauthorized, 'token'), $pine['slug']); throw new RuntimeException('Unauthorized user resolved a property context.'); } catch (CommunityActionPropertyForbiddenException) {}
    $deliveries = [];
    $email = new SupplyOrderEmailSender(['test_mode' => true, 'test_email' => 'noc@example.test', 'recipient_email' => 'production@example.invalid', 'app_url' => 'https://gateway.example.test'], static function ($to, $subject, $html, $text) use (&$deliveries): bool { $deliveries[] = compact('to', 'subject', 'html', 'text'); return true; });
    $repo = new SupplyOrderRepository($db);
    $makeService = static fn (SupplyOrderEmailSender $sender): SupplyOrderService => new SupplyOrderService($repo, $container->get(SupplyOrderValidator::class), $container->get(InitializationTransactionInterface::class), $ids, $container->get(ClockInterface::class), $container->get(AuditService::class), $sender);
    $service = $makeService($email);
    $first = $service->submit($pineContext, ['request_html' => '<p><strong>2 cases of copy paper</strong><img src="data:x"><script>x</script></p><p>toner for Brother L2750 printer</p><p>1 box black ink pens</p>']);
    $second = $service->submit($pineContext, ['request_html' => '<p>4 cases of gloves</p>']);
    $service->submit($oakContext, ['request_html' => '<p>Oak-only supplies</p>']);
    $failed = $makeService(new SupplyOrderEmailSender(['test_mode' => true, 'test_email' => 'noc@example.test', 'app_url' => 'https://gateway.example.test'], static fn (): bool => false))->submit($pineContext, ['request_html' => '<p>Email failure still persists</p>']);
    $pineOrders = $service->listForProperty($pine['id']);
    if (count($pineOrders) !== 3 || $pineOrders[1]['public_id'] !== $second['public_id'] || $pineOrders[2]['public_id'] !== $first['public_id']) throw new RuntimeException('Property history ordering mismatch.');
    $preview = (new SupplyOrderPreviewFormatter())->format($pineOrders[2]['request_html']);
    if ($preview !== '2 cases of copy paper, toner for Brother L2750 printer, 1 box black ink pens') throw new RuntimeException('History preview normalization mismatch.');
    if ($service->detailForProperty($first['public_id'], $oak['id']) !== null) throw new RuntimeException('Cross-property detail leaked.');
    if ($failed['email_sent'] || $service->detailForProperty($failed['public_id'], $pine['id']) === null) throw new RuntimeException('Post-commit email failure semantics mismatch.');
    $detail = $service->detailForProperty($first['public_id'], $pine['id']);
    if (str_contains($detail['request_html'], 'img')) throw new RuntimeException('Rich text sanitizer mismatch.');
    if ($detail['submitted_by_name'] !== 'Supply Certification' || !preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/D', $first['public_id']) || $detail['submitted_at'] === '') throw new RuntimeException('Authoritative record identity mismatch.');
    $access->revoke($user->id, $pine['id']);
    try { $resolver->resolve($request, $pine['slug']); throw new RuntimeException('Revoked access remained effective.'); } catch (CommunityActionPropertyForbiddenException) {}
    $logs = json_encode($db->query("SELECT after_data FROM audit_logs WHERE event_type='supply_order.submitted' AND user_id={$user->id}")->fetch_all(MYSQLI_ASSOC));
    foreach (['copy paper', 'gloves', 'Oak-only', 'Email failure', '@example'] as $private) if (str_contains($logs, $private)) throw new RuntimeException('Audit privacy mismatch.');
    $columns = array_column($db->query("SHOW COLUMNS FROM supply_orders")->fetch_all(MYSQLI_ASSOC), 'Field');
    if (in_array('status', $columns, true) || $db->query("SHOW TABLES LIKE 'supply_order_history'")->num_rows !== 0) throw new RuntimeException('Immutable schema mismatch.');
    if (count($deliveries) !== 3 || array_unique(array_column($deliveries, 'to')) !== ['noc@example.test']) throw new RuntimeException('Email routing mismatch.');
    echo "property_scoping=passed\nunauthorized_user=denied\nnewest_first=passed\npreview_normalization=passed\nauthoritative_identity=passed\nwrong_property=404_semantics\nrevocation=passed\nemail_failure_persisted=passed\ndeliveries=3_fake_only\nimmutable_schema=passed\naudit_privacy=passed\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    $code = 1;
} finally {
    $cleanup($db);
    $residue = (int) $db->query("SELECT COUNT(*) FROM users WHERE username LIKE 'supplycert%'")->fetch_row()[0] + (int) $db->query("SELECT COUNT(*) FROM properties WHERE slug LIKE 'supply-cert-%'")->fetch_row()[0];
    echo "fixture_residue={$residue}\n";
    $db->close();
}
exit($code ?? 0);

<?php
// AJAX endpoint: batch-saves Annual Plan edits made in index.php's Edit mode.
// Expects a JSON body: [{company, dept, month, state}, ...] where state is
// '' | 'P' | 'C'. Applies all changes in one transaction (all-or-nothing).
require_once 'db.php';
header('Content-Type: application/json');

$db = getDB();

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$changes = [];
foreach ($body as $entry) {
    $company = $entry['company'] ?? '';
    $dept    = trim($entry['dept'] ?? '');
    $month   = (int)($entry['month'] ?? 0);
    $state   = $entry['state'] ?? '';

    if (!in_array($company, ['GW', 'IND'], true) || $dept === '' || $month < 1 || $month > 12
        || !in_array($state, ['', 'P', 'C'], true)) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid entry: " . json_encode($entry)]);
        exit;
    }
    $changes[] = [$company, $dept, $month, $state];
}

try {
    $db->beginTransaction();

    $del = $db->prepare("DELETE FROM plan_entries WHERE company = ? AND dept_name = ? AND month = ?");
    $ins = $db->prepare("INSERT INTO plan_entries (company, dept_name, equipment_type, month) VALUES (?,?,?,?)");

    foreach ($changes as [$company, $dept, $month, $state]) {
        $del->execute([$company, $dept, $month]);
        if ($state !== '') {
            $type = $state === 'P' ? 'Printer' : 'PC';
            $ins->execute([$company, $dept, $type, $month]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'count' => count($changes)]);
} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

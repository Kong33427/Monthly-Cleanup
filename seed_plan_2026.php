<?php
// One-time seed: inserts the 2026 annual cleaning plan (Printer & Computer)
// as schedules, based on the department/month plan sheet.
// Safe to re-run — catches unique-constraint violations on (company,
// equipment_type, scheduled_date, dept_name) so already-inserted rows are skipped.
require_once 'db.php';
$db = getDB();

$YEAR = 2026;
$DAY  = 1; // scheduled_date = the 1st of each plan month

// [company, equipment_type, dept_name, [months...]]
$plan = [
    // GW
    ['GW', 'Printer', 'บัญชี',         [1,3,5,7,9,11]],
    ['GW', 'PC',      'บัญชี',         [4]],
    ['GW', 'Printer', 'ประสานงานขาย',  [1,3,5,7,9,11]],
    ['GW', 'PC',      'ประสานงานขาย',  [4]],
    ['GW', 'PC',      'ขาย',           [4]],
    ['GW', 'PC',      'การตลาด',       [4]],
    ['GW', 'PC',      'ต่างประเทศ',     [4]],
    ['GW', 'PC',      'Job control',   [4]],
    ['GW', 'PC',      'สำนักงานกลาง',   [4]],
    ['GW', 'Printer', 'จัดซื้อ',        [1,3,5,7,9,11]],
    ['GW', 'PC',      'จัดซื้อ',        [4]],
    ['GW', 'PC',      'บุคคล',         [4]],
    ['GW', 'PC',      'วิศวกรรม',       [11]],
    ['GW', 'Printer', 'ผลิต',          [1,3,5,7,9,11]],
    ['GW', 'PC',      'ผลิต',          [6]],
    ['GW', 'PC',      'วางแผน',        [6]],
    ['GW', 'PC',      'QA-QC',        [6]],
    ['GW', 'PC',      'สโตร์',         [6]],
    ['GW', 'Printer', 'คลังสินค้า',     [1,3,5,7,9,11]],
    ['GW', 'PC',      'คลังสินค้า',     [6]],
    ['GW', 'PC',      'ออกแบบ',        [6]],
    ['GW', 'PC',      'ไอที',          [6]],
    // เครื่องจักร has no scheduled cleanups in the plan

    // IND
    ['IND', 'PC',      'บุคคล',    [6]],
    ['IND', 'Printer', 'บัญชี',    [1,3,5,7,9,11]],
    ['IND', 'PC',      'บัญชี',    [6]],
    ['IND', 'PC',      'ตรวจสอบ', [6]],
    ['IND', 'Printer', 'คลังสินค้า', [1,3,5,7,9,11]],
    ['IND', 'PC',      'คลังสินค้า', [6]],
    ['IND', 'Printer', 'จัดซื้อ',   [1,3,5,7,9,11]],
    ['IND', 'PC',      'จัดซื้อ',   [6]],
    ['IND', 'Printer', 'ผลิต',     [1,3,5,7,9,11]],
    ['IND', 'PC',      'ผลิต',     [6]],
    ['IND', 'PC',      'วางแผน',   [6]],
];

$stmt = $db->prepare("INSERT INTO schedules (company, equipment_type, scheduled_date, dept_name) VALUES (?,?,?,?)");

// Only seed from the current month forward — past months are treated as
// already missed rather than backfilled as overdue backlog.
$cutoff = date('Y-m-01');

$inserted = 0;
$skipped  = 0;
$pastSkipped = 0;
$errors   = [];

foreach ($plan as [$company, $type, $dept, $months]) {
    foreach ($months as $m) {
        $date = sprintf('%04d-%02d-%02d', $YEAR, $m, $DAY);
        if ($date < $cutoff) {
            $pastSkipped++;
            continue;
        }
        try {
            $stmt->execute([$company, $type, $date, $dept]);
            $inserted++;
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                $skipped++;
            } else {
                $errors[] = "{$company}-{$type}-{$date}-{$dept}: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seed 2026 Plan | Monthly Cleanup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px">
    <h5 class="mb-4">2026 Cleaning Plan Seed</h5>
    <div class="alert alert-success">Inserted <strong><?= $inserted ?></strong> new schedule(s).</div>
    <?php if ($skipped): ?>
        <div class="alert alert-info"><?= $skipped ?> already existed and were skipped.</div>
    <?php endif; ?>
    <?php if ($pastSkipped): ?>
        <div class="alert alert-secondary"><?= $pastSkipped ?> fell before this month and were not seeded (no overdue backlog).</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <a href="index.php" class="btn btn-primary">Go to Calendar &rarr;</a>
    <a href="schedule.php" class="btn btn-outline-secondary">View Schedules</a>
</div>
</body>
</html>

<?php
// Monthly seed: inserts one month's slice of the 2026 annual cleaning plan
// (Printer & Computer) as schedules, based on the department/month plan sheet.
// Meant to be re-run once each month as it starts. By default it only ever
// targets the CURRENT calendar month. If there's nothing to create there
// (no plan entries this month, or everything already exists), it asks
// before creating a future month's schedules ahead of time — it never does
// that silently.
require_once 'db.php';
$db = getDB();

$DAY  = 1; // scheduled_date = the 1st of each plan month

// Read the live, editable plan from plan_entries (edited via index.php's
// Annual Plan table) and group into [company, type, dept, months] tuples.
$planRows = $db->query("SELECT company, dept_name, equipment_type, month FROM plan_entries")->fetchAll(PDO::FETCH_ASSOC);
$grouped = [];
foreach ($planRows as $r) {
    $key = $r['company'] . '|' . $r['dept_name'] . '|' . $r['equipment_type'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = ['company' => $r['company'], 'type' => $r['equipment_type'], 'dept' => $r['dept_name'], 'months' => []];
    }
    $grouped[$key]['months'][] = (int)$r['month'];
}
$plan = [];
foreach ($grouped as $g) {
    $plan[] = [$g['company'], $g['type'], $g['dept'], $g['months']];
}

function monthHasPlanEntries(array $plan, int $month): bool {
    foreach ($plan as [, , , $months]) {
        if (in_array($month, $months, true)) return true;
    }
    return false;
}

// Finds the next month after $afterMonth (within the current year) that has
// any plan entries at all. Returns null if none remain this year.
function nextPlannedMonth(array $plan, int $afterMonth): ?int {
    for ($m = $afterMonth + 1; $m <= 12; $m++) {
        if (monthHasPlanEntries($plan, $m)) return $m;
    }
    return null;
}

function seedMonth(PDO $db, array $plan, int $year, int $month, int $day): array {
    $stmt = $db->prepare("INSERT INTO schedules (company, equipment_type, scheduled_date, dept_name) VALUES (?,?,?,?)");
    $inserted = 0;
    $skipped  = 0;
    $errors   = [];
    foreach ($plan as [$company, $type, $dept, $months]) {
        if (!in_array($month, $months, true)) continue;
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        try {
            $stmt->execute([$company, $type, $date, $dept]);
            $inserted++;
        } catch (PDOException $e) {
            if (isDuplicateKeyError($e)) {
                $skipped++;
            } else {
                $errors[] = "{$company}-{$type}-{$date}-{$dept}: " . $e->getMessage();
            }
        }
    }
    return [$inserted, $skipped, $errors];
}

$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');

$confirmAhead = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ahead_month']);

$result = null;   // [inserted, skipped, errors, month, year]
$askAhead = null; // month number to offer, or null

if ($confirmAhead) {
    $aheadMonth = (int)$_POST['ahead_month'];
    [$inserted, $skipped, $errors] = seedMonth($db, $plan, $currentYear, $aheadMonth, $DAY);
    $result = [$inserted, $skipped, $errors, $aheadMonth, $currentYear];
} else {
    [$inserted, $skipped, $errors] = seedMonth($db, $plan, $currentYear, $currentMonth, $DAY);
    if ($inserted === 0 && empty($errors)) {
        // Nothing new for the current month — either the plan has no
        // entries this month, or everything's already been created.
        $askAhead = nextPlannedMonth($plan, $currentMonth);
    }
    $result = [$inserted, $skipped, $errors, $currentMonth, $currentYear];
}

[$inserted, $skipped, $errors, $resultMonth, $resultYear] = $result;
$resultMonthName = date('F Y', mktime(0, 0, 0, $resultMonth, 1, $resultYear));
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
    <h5 class="mb-4">2026 Cleaning Plan Seed — <?= htmlspecialchars($resultMonthName) ?></h5>

    <?php if ($inserted > 0): ?>
        <div class="alert alert-success">Inserted <strong><?= $inserted ?></strong> new schedule(s) for <?= htmlspecialchars($resultMonthName) ?>.</div>
    <?php elseif (empty($errors) && $askAhead === null && !$confirmAhead): ?>
        <div class="alert alert-info">Nothing to create for <?= htmlspecialchars($resultMonthName) ?> — the plan has no more months defined this year.</div>
    <?php elseif (empty($errors) && $askAhead === null): ?>
        <div class="alert alert-info">Nothing new to create for <?= htmlspecialchars($resultMonthName) ?> — already up to date.</div>
    <?php endif; ?>

    <?php if ($skipped): ?>
        <div class="alert alert-secondary"><?= $skipped ?> already existed and were skipped.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($askAhead !== null):
        $aheadName = date('F Y', mktime(0, 0, 0, $askAhead, 1, $currentYear));
    ?>
    <div class="alert alert-warning">
        There's nothing to schedule for <?= htmlspecialchars(date('F', mktime(0,0,0,$currentMonth,1))) ?> yet.
        Would you like to create <strong><?= htmlspecialchars($aheadName) ?></strong>'s schedule ahead of time?
    </div>
    <form method="post" class="d-flex gap-2 mb-3">
        <input type="hidden" name="ahead_month" value="<?= $askAhead ?>">
        <button type="submit" class="btn btn-warning">
            <i class="bi bi-calendar2-plus"></i> Yes, create <?= htmlspecialchars($aheadName) ?> now
        </button>
    </form>
    <?php endif; ?>

    <a href="index.php" class="btn btn-primary">Go to Calendar &rarr;</a>
    <a href="schedule.php" class="btn btn-outline-secondary">View Schedules</a>
</div>
</body>
</html>

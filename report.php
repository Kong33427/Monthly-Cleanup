<?php
require_once 'db.php';
$db = getDB();

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
if ($month < 1 || $month > 12) { $month = (int)date('n'); $year = (int)date('Y'); }

$monthName = date('F Y', mktime(0,0,0,$month,1,$year));

// Fetch all schedules for the month with their records
$stmt = $db->prepare(
    "SELECT s.company, s.equipment_type, s.scheduled_date, s.id AS schedule_id,
            r.quantity, r.dust_blowing, r.rinse_detergent,
            r.dept_name, r.confirmed_by, r.cleaned_date,
            r.signature_data, r.notes
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     WHERE MONTH(s.scheduled_date) = ? AND YEAR(s.scheduled_date) = ?
     ORDER BY s.scheduled_date, s.company, s.equipment_type"
);
$stmt->bind_param('ii', $month, $year);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build a lookup of which groups appear
$groups = [];
foreach ($records as $r) {
    $key = $r['company'] . '-' . $r['equipment_type'];
    $groups[$key] = $r;
}

// Ensure all 4 expected groups are shown
$allGroups = [
    'GW-PC'       => ['company'=>'GW',  'equipment_type'=>'PC'],
    'GW-Printer'  => ['company'=>'GW',  'equipment_type'=>'Printer'],
    'IND-PC'      => ['company'=>'IND', 'equipment_type'=>'PC'],
    'IND-Printer' => ['company'=>'IND', 'equipment_type'=>'Printer'],
];

$page_title  = "Report — {$monthName}";
$active_page = 'report';
include 'header.php';
?>

<div class="container-fluid py-4">

    <!-- Controls (no-print) -->
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4 no-print">
        <form method="get" class="d-flex gap-2 align-items-center">
            <select name="month" class="form-select form-select-sm" style="width:auto">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
                    <?= date('F', mktime(0,0,0,$m,1)) ?>
                </option>
                <?php endfor; ?>
            </select>
            <input type="number" name="year" class="form-control form-control-sm" style="width:90px"
                   value="<?= $year ?>" min="2020" max="2099">
            <button type="submit" class="btn btn-sm btn-primary">View</button>
        </form>
        <div class="ms-auto d-flex gap-2">
            <a href="export.php?month=<?= $month ?>&year=<?= $year ?>"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Print / PDF
            </button>
        </div>
    </div>

    <!-- Report title (print visible) -->
    <div class="report-title mb-3">
        <h4 class="fw-bold mb-0">Monthly Cleanup Report</h4>
        <div class="text-muted"><?= htmlspecialchars($monthName) ?></div>
        <div class="text-muted small print-only">Generated: <?= date('Y-m-d H:i') ?></div>
    </div>

    <!-- Summary badges (no-print) -->
    <?php
    $total = count($records);
    $done  = count(array_filter($records, function($r) { return !empty($r['cleaned_date']); }));
    ?>
    <div class="d-flex gap-3 mb-3 no-print">
        <span class="badge bg-primary fs-6"><?= $total ?> Scheduled</span>
        <span class="badge bg-success fs-6"><?= $done ?> Completed</span>
        <span class="badge bg-warning text-dark fs-6"><?= $total - $done ?> Pending</span>
    </div>

    <!-- Report table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm report-table mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Sched. Date</th>
                            <th>Cleaned Date</th>
                            <th>Qty</th>
                            <th>Dust<br>Blowing</th>
                            <th>Rinse w/<br>Detergent</th>
                            <th>Department</th>
                            <th>Confirmed By</th>
                            <th>Signature</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Show scheduled records
                    foreach ($records as $r):
                        $isDone = !empty($r['cleaned_date']);
                        $isOverdue = !$isDone && $r['scheduled_date'] < date('Y-m-d');
                    ?>
                    <tr class="<?= $isDone ? 'table-success' : ($isOverdue ? 'table-danger' : '') ?>">
                        <td class="fw-semibold"><?= htmlspecialchars($r['company']) ?></td>
                        <td>
                            <?= $r['equipment_type'] === 'PC' ? '💻' : '🖨️' ?>
                            <?= htmlspecialchars($r['equipment_type']) ?>
                        </td>
                        <td><?= htmlspecialchars($r['scheduled_date']) ?></td>
                        <td><?= $r['cleaned_date'] ? htmlspecialchars($r['cleaned_date']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $isDone ? (int)$r['quantity'] : '—' ?></td>
                        <td class="text-center">
                            <?php if ($isDone): ?>
                                <span class="text-<?= $r['dust_blowing'] ? 'success' : 'danger' ?>">
                                    <?= $r['dust_blowing'] ? '&#x2713;' : '&#x2715;' ?>
                                </span>
                            <?php else: echo '—'; endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($isDone): ?>
                                <span class="text-<?= $r['rinse_detergent'] ? 'success' : 'danger' ?>">
                                    <?= $r['rinse_detergent'] ? '&#x2713;' : '&#x2715;' ?>
                                </span>
                            <?php else: echo '—'; endif; ?>
                        </td>
                        <td><?= $r['dept_name'] ? htmlspecialchars($r['dept_name']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $r['confirmed_by'] ? htmlspecialchars($r['confirmed_by']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center">
                            <?php if ($r['signature_data']): ?>
                                <img src="<?= $r['signature_data'] ?>" alt="Sig"
                                     style="max-width:100px;max-height:50px;display:block;margin:auto">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isDone): ?>
                                <span class="badge bg-success">Done</span>
                            <?php elseif ($isOverdue): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            No schedules found for <?= htmlspecialchars($monthName) ?>.
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Signature section for print (larger view) -->
    <?php foreach ($records as $r): if (!$r['signature_data']) continue; ?>
    <div class="mt-4 print-only">
        <div class="d-flex gap-4 align-items-end">
            <div>
                <div class="small text-muted mb-1">
                    <?= htmlspecialchars($r['company']) ?>-<?= htmlspecialchars($r['equipment_type']) ?>
                    Signature (<?= htmlspecialchars($r['confirmed_by']) ?>)
                </div>
                <div class="border p-1" style="width:200px;height:80px">
                    <img src="<?= $r['signature_data'] ?>" alt="Signature"
                         style="max-width:100%;max-height:100%">
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Print footer -->
    <div class="mt-4 print-only text-muted small text-end">
        Monthly Cleanup Tracker &mdash; Printed <?= date('Y-m-d H:i') ?>
    </div>

</div>

<?php include 'footer.php'; ?>

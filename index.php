<?php
require_once 'db.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$db = getDB();

$stmt = $db->prepare(
    "SELECT s.*, r.id AS record_id
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     WHERE MONTH(s.scheduled_date) = ? AND YEAR(s.scheduled_date) = ?
     ORDER BY s.scheduled_date, s.company, s.equipment_type"
);
$stmt->bind_param('ii', $month, $year);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Index by day
$byDay = [];
foreach ($rows as $r) {
    $byDay[(int)date('j', strtotime($r['scheduled_date']))][] = $r;
}

$total     = count($rows);
$completed = count(array_filter($rows, function($r) { return !empty($r['record_id']); }));
$today     = date('Y-m-d');

$firstDay    = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$startDow    = (int)date('w', $firstDay);
$monthName   = date('F Y', $firstDay);

$prevMonth = $month === 1  ? 12 : $month - 1;
$prevYear  = $month === 1  ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1  : $month + 1;
$nextYear  = $month === 12 ? $year + 1 : $year;

$page_title  = 'Calendar';
$active_page = 'calendar';
include 'header.php';
?>

<div class="container-fluid py-4">

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-primary"><?= $total ?></div>
                    <div class="text-muted small">Scheduled</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-success"><?= $completed ?></div>
                    <div class="text-muted small">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-warning"><?= $total - $completed ?></div>
                    <div class="text-muted small">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 d-flex align-items-center">
            <a href="report.php?month=<?= $month ?>&year=<?= $year ?>"
               class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-file-earmark-bar-graph"></i> View Report
            </a>
        </div>
    </div>

    <!-- Calendar card -->
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>"
               class="btn btn-sm btn-outline-secondary no-print">
                <i class="bi bi-chevron-left"></i>
            </a>
            <h5 class="mb-0"><?= htmlspecialchars($monthName) ?></h5>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>"
               class="btn btn-sm btn-outline-secondary no-print">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="calendar-grid">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow): ?>
                    <div class="cal-header"><?= $dow ?></div>
                <?php endforeach; ?>

                <?php
                for ($i = 0; $i < $startDow; $i++) {
                    echo '<div class="cal-cell empty"></div>';
                }

                for ($day = 1; $day <= $daysInMonth; $day++):
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = ($dateStr === $today);
                ?>
                <div class="cal-cell <?= $isToday ? 'today' : '' ?>">
                    <div class="cal-day-num <?= $isToday ? 'text-primary fw-bold' : '' ?>"><?= $day ?></div>
                    <?php if (isset($byDay[$day])): ?>
                        <?php foreach ($byDay[$day] as $s):
                            $done     = !empty($s['record_id']);
                            $overdue  = !$done && $dateStr < $today;
                            $cls = $done ? 'bg-success' : ($overdue ? 'bg-danger' : 'bg-warning text-dark');
                            $icon = $s['equipment_type'] === 'PC' ? '&#x1F4BB;' : '&#x1F5A8;';
                        ?>
                        <a href="cleanup.php?id=<?= $s['id'] ?>"
                           class="cal-event badge <?= $cls ?> d-block mb-1 text-decoration-none">
                            <?= $icon ?> <?= htmlspecialchars($s['company']) ?>-<?= htmlspecialchars($s['equipment_type']) ?>
                            <?php if ($done): ?><i class="bi bi-check-circle-fill"></i><?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>

                <?php
                $filled = $startDow + $daysInMonth;
                $tail   = (7 - ($filled % 7)) % 7;
                for ($i = 0; $i < $tail; $i++) {
                    echo '<div class="cal-cell empty"></div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Legend + Add button -->
    <div class="d-flex flex-wrap align-items-center gap-3 mt-3 no-print">
        <span class="badge bg-success">Completed</span>
        <span class="badge bg-warning text-dark">Pending</span>
        <span class="badge bg-danger">Overdue</span>
        <div class="ms-auto">
            <a href="schedule.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Schedule
            </a>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

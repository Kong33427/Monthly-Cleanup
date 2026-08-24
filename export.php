<?php
require_once 'db.php';
$db = getDB();

// month = 0 means "All Months" (whole year)
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
if ($month < 0 || $month > 12) { $month = 0; $year = (int)date('Y'); }

$monthLabel = $month === 0 ? "{$year}-all" : date('Y-m', mktime(0,0,0,$month,1,$year));

// Overdue is computed against PHP's clock (not the DB server's) so it stays
// consistent with report.php/index.php/cleanup.php/schedule.php, which all
// compare against date('Y-m-d') — the DB is a separate host from the web
// server, so their clocks are not guaranteed to agree.
$today = date('Y-m-d');

$sql = "SELECT s.company, s.equipment_type, s.scheduled_date,
            r.cleaned_date, r.quantity, r.dust_blowing, r.rinse_detergent,
            r.dept_name, r.confirmed_by, r.notes,
            CASE WHEN r.id IS NOT NULL THEN 'Completed' ELSE
                CASE WHEN s.scheduled_date < ? THEN 'Overdue' ELSE 'Pending' END
            END AS status
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     WHERE " . ($month === 0 ? "YEAR(s.scheduled_date) = ?" : "MONTH(s.scheduled_date) = ? AND YEAR(s.scheduled_date) = ?") . "
     ORDER BY s.scheduled_date, s.company, s.equipment_type";
$stmt = $db->prepare($sql);
$stmt->execute($month === 0 ? [$today, $year] : [$today, $month, $year]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status filter: all | completed | pending | overdue
$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'completed', 'pending', 'overdue'])) $status = 'all';
if ($status !== 'all') {
    $rows = array_values(array_filter($rows, function($r) use ($status) {
        return strtolower($r['status']) === $status;
    }));
}

$filename = "cleanup_report_{$monthLabel}" . ($status !== 'all' ? "_{$status}" : '') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'Company', 'Equipment Type', 'Scheduled Date', 'Cleaned Date',
    'Quantity', 'Dust Blowing', 'Rinse w/ Detergent',
    'Department', 'Confirmed By', 'Notes', 'Status'
]);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['company'],
        $r['equipment_type'],
        $r['scheduled_date'],
        $r['cleaned_date'] ?? '',
        $r['quantity'] ?? '',
        $r['dust_blowing'] === null ? '' : ($r['dust_blowing'] ? 'Yes' : 'No'),
        $r['rinse_detergent'] === null ? '' : ($r['rinse_detergent'] ? 'Yes' : 'No'),
        $r['dept_name'] ?? '',
        $r['confirmed_by'] ?? '',
        $r['notes'] ?? '',
        $r['status'],
    ]);
}

fclose($out);

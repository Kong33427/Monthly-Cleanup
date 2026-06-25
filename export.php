<?php
require_once 'db.php';
$db = getDB();

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
if ($month < 1 || $month > 12) { $month = (int)date('n'); $year = (int)date('Y'); }

$monthLabel = date('Y-m', mktime(0,0,0,$month,1,$year));

$stmt = $db->prepare(
    "SELECT s.company, s.equipment_type, s.scheduled_date,
            COALESCE(r.cleaned_date, '') AS cleaned_date,
            COALESCE(r.quantity, '') AS quantity,
            COALESCE(r.dust_blowing, '') AS dust_blowing,
            COALESCE(r.rinse_detergent, '') AS rinse_detergent,
            COALESCE(r.dept_name, '') AS dept_name,
            COALESCE(r.confirmed_by, '') AS confirmed_by,
            COALESCE(r.notes, '') AS notes,
            CASE WHEN r.id IS NOT NULL THEN 'Completed' ELSE
                CASE WHEN s.scheduled_date < CURDATE() THEN 'Overdue' ELSE 'Pending' END
            END AS status
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     WHERE MONTH(s.scheduled_date) = ? AND YEAR(s.scheduled_date) = ?
     ORDER BY s.scheduled_date, s.company, s.equipment_type"
);
$stmt->bind_param('ii', $month, $year);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$filename = "cleanup_report_{$monthLabel}.csv";

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
        $r['cleaned_date'],
        $r['quantity'],
        $r['dust_blowing'] === '' ? '' : ($r['dust_blowing'] ? 'Yes' : 'No'),
        $r['rinse_detergent'] === '' ? '' : ($r['rinse_detergent'] ? 'Yes' : 'No'),
        $r['dept_name'],
        $r['confirmed_by'],
        $r['notes'],
        $r['status'],
    ]);
}

fclose($out);

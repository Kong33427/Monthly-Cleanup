<?php
require_once 'db.php';
require_once 'report_query.php';
$db = getDB();

[$month, $year] = normalizeMonthYear($_GET['month'] ?? null, $_GET['year'] ?? null);
$status = normalizeStatus($_GET['status'] ?? null);

$monthLabel = $month === 0 ? "{$year}-all" : date('Y-m', mktime(0,0,0,$month,1,$year));

[$sql, $params] = buildReportQuery(
    "s.company, s.equipment_type, s.scheduled_date,
     r.cleaned_date, r.quantity, r.dust_blowing, r.rinse_detergent,
     r.dept_name, r.confirmed_by, r.notes",
    $month, $year, $status
);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$today = date('Y-m-d');
foreach ($rows as $r) {
    $isDone = !empty($r['cleaned_date']);
    $statusLabel = $isDone ? 'Completed' : (isOverdue($r, $today) ? 'Overdue' : 'Pending');
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
        $statusLabel,
    ]);
}

fclose($out);

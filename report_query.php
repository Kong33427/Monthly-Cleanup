<?php
// Shared query-building logic for report.php and export.php so the two
// never drift out of sync on filtering, sorting, or the overdue rule.

function isOverdue(array $r, string $today): bool {
    return empty($r['cleaned_date']) && $r['scheduled_date'] < $today;
}

function normalizeStatus(?string $status): string {
    $status = $status ?? 'all';
    return in_array($status, ['all', 'completed', 'pending', 'overdue'], true) ? $status : 'all';
}

// month = 0 means "All Months" for the given year.
function normalizeMonthYear($monthParam, $yearParam): array {
    $month = $monthParam !== null ? (int)$monthParam : 0;
    $year  = $yearParam  !== null ? (int)$yearParam  : (int)date('Y');
    if ($month < 0 || $month > 12) { $month = 0; $year = (int)date('Y'); }
    return [$month, $year];
}

// Builds the full SELECT/FROM/WHERE/ORDER BY for the report+export query,
// pushing the month/year and status filters into SQL rather than fetching
// everything and discarding rows in PHP. $selectCols is the raw column list.
function buildReportQuery(string $selectCols, int $month, int $year, string $status): array {
    $where  = $month === 0 ? "YEAR(s.scheduled_date) = ?" : "MONTH(s.scheduled_date) = ? AND YEAR(s.scheduled_date) = ?";
    $params = $month === 0 ? [$year] : [$month, $year];

    if ($status === 'completed') {
        $where .= " AND r.cleaned_date IS NOT NULL";
    } elseif ($status === 'pending') {
        $where .= " AND r.cleaned_date IS NULL AND s.scheduled_date >= ?";
        $params[] = date('Y-m-d');
    } elseif ($status === 'overdue') {
        $where .= " AND r.cleaned_date IS NULL AND s.scheduled_date < ?";
        $params[] = date('Y-m-d');
    }

    $sql = "SELECT {$selectCols}
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     WHERE {$where}
     ORDER BY CASE WHEN r.cleaned_date IS NULL THEN 1 ELSE 0 END, r.cleaned_date DESC, s.scheduled_date, s.company, s.equipment_type";

    return [$sql, $params];
}

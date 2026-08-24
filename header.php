<?php
// Usage: set $page_title and $active_page before including this file
$page_title  = $page_title  ?? 'Monthly Cleanup';
$active_page = $active_page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> | Monthly Cleanup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <?php $styleMtime = @filemtime(__DIR__ . '/assets/style.css'); ?>
    <link rel="stylesheet" href="assets/style.css<?= $styleMtime ? "?v={$styleMtime}" : '' ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="index.php">
            <i class="bi bi-clipboard2-check-fill me-1 text-info"></i>Monthly Cleanup
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $active_page === 'calendar'  ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-calendar3"></i> Calendar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page === 'schedule'  ? 'active' : '' ?>" href="schedule.php">
                        <i class="bi bi-plus-circle"></i> Schedules
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page === 'report'    ? 'active' : '' ?>" href="report.php">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reports
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

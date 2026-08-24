<?php
// Deprecated: this app now runs on SQL Server, not MySQL/XAMPP.
// The current schema (including dept_name and the 4-column unique key) is
// already the baseline defined in setup.php — there is nothing left for
// this script to migrate.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migrate | Monthly Cleanup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px">
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <h5 class="text-muted mb-3">This step is no longer needed</h5>
            <p class="text-muted">Monthly Cleanup now runs on SQL Server. The <code>schedules</code>/<code>cleanup_records</code> schema (including <code>dept_name</code> and its unique key) is already the baseline of the database — there is no migration to run from this page.</p>
            <a href="schedule.php" class="btn btn-primary">Go to Schedules &rarr;</a>
        </div>
    </div>
</div>
</body>
</html>

<?php
// Deprecated: this app now runs on SQL Server, not MySQL/XAMPP.
// The monthly_cleanup database and its tables are created directly on the
// SQL Server instance configured in db_config.php — there is no setup step
// to run from the browser anymore.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup | Monthly Cleanup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:560px">
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <h5 class="text-muted mb-3">This step is no longer needed</h5>
            <p class="text-muted">Monthly Cleanup now runs on SQL Server. The database and tables are managed directly on the server (see <code>db_config.php</code> for connection settings) instead of being created from this page.</p>
            <a href="index.php" class="btn btn-primary mt-2">Go to Dashboard &rarr;</a>
        </div>
    </div>
</div>
</body>
</html>

<?php
// One-time (and safe-to-re-run) SQL Server schema provisioning. Creates the
// monthly_cleanup database and its tables if they don't already exist —
// this is the DDL source of truth for standing the app up on a fresh
// SQL Server instance (e.g. disaster recovery, a new environment).
$cfg      = file_exists(__DIR__ . '/db_config.php') ? (require __DIR__ . '/db_config.php') : [];
$server   = $cfg['server']   ?? 'localhost\SQLEXPRESS';
$database = $cfg['database'] ?? 'monthly_cleanup';
$username = $cfg['username'] ?? '';
$password = $cfg['password'] ?? '';
$trusted  = $cfg['trusted']  ?? false;

$errors = [];

try {
    // Connect at server level first (no Database=) so we can create the DB if missing.
    $pdo = new PDO(
        "sqlsrv:Server={$server};TrustServerCertificate=1",
        $trusted ? null : $username,
        $trusted ? null : $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $dbNameEsc = str_replace(']', ']]', $database);
    $pdo->exec("IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = " . $pdo->quote($database) . ")
        EXEC('CREATE DATABASE [{$dbNameEsc}]')");

    $pdo->exec("USE [{$dbNameEsc}]");

    $pdo->exec("IF OBJECT_ID('dbo.schedules', 'U') IS NULL
        CREATE TABLE dbo.schedules (
            id INT IDENTITY(1,1) PRIMARY KEY,
            company NVARCHAR(10) NOT NULL CHECK (company IN ('GW','IND')),
            equipment_type NVARCHAR(10) NOT NULL CHECK (equipment_type IN ('PC','Printer')),
            scheduled_date DATE NOT NULL,
            dept_name NVARCHAR(100) NULL,
            created_at DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
            CONSTRAINT uq_schedule UNIQUE (company, equipment_type, scheduled_date, dept_name)
        )");

    $pdo->exec("IF OBJECT_ID('dbo.cleanup_records', 'U') IS NULL
        CREATE TABLE dbo.cleanup_records (
            id INT IDENTITY(1,1) PRIMARY KEY,
            schedule_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            dust_blowing BIT NOT NULL DEFAULT 0,
            rinse_detergent BIT NOT NULL DEFAULT 0,
            dept_name NVARCHAR(100) NULL,
            confirmed_by NVARCHAR(100) NULL,
            signature_data NVARCHAR(MAX) NULL,
            signature_type NVARCHAR(10) NOT NULL DEFAULT 'drawn',
            cleaned_date DATE NULL,
            notes NVARCHAR(MAX) NULL,
            created_at DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
            CONSTRAINT fk_cleanup_schedule FOREIGN KEY (schedule_id) REFERENCES dbo.schedules(id) ON DELETE CASCADE
        )");
} catch (PDOException $e) {
    $errors[] = $e->getMessage();
}
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
            <?php if (empty($errors)): ?>
                <div class="display-1 mb-3">&#10003;</div>
                <h3 class="text-success mb-3">Setup Complete!</h3>
                <p class="text-muted">Database <strong><?= htmlspecialchars($database) ?></strong> and all tables are ready on <strong><?= htmlspecialchars($server) ?></strong>.</p>
                <a href="index.php" class="btn btn-primary mt-2">Go to Dashboard &rarr;</a>
            <?php else: ?>
                <div class="display-1 mb-3 text-danger">&#x26A0;</div>
                <h3 class="text-danger mb-3">Setup Failed</h3>
                <?php foreach ($errors as $e): ?>
                    <p class="text-danger small"><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-center text-muted small mt-3">Run this page only once. You can safely re-run it — tables won't be overwritten.</p>
</div>
</body>
</html>

<?php
$conn = new mysqli('localhost', 'root', '', '');
if ($conn->connect_error) {
    die('<p style="color:red;font-family:sans-serif">Cannot connect to MySQL: ' . htmlspecialchars($conn->connect_error) . '</p>');
}

$errors = [];

$conn->query("CREATE DATABASE IF NOT EXISTS `monthly_cleanup` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if ($conn->error) $errors[] = 'Create DB: ' . $conn->error;

$conn->select_db('monthly_cleanup');

$conn->query("CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company ENUM('GW','IND') NOT NULL,
    equipment_type ENUM('PC','Printer') NOT NULL,
    scheduled_date DATE NOT NULL,
    dept_name VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schedule (company, equipment_type, scheduled_date, dept_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($conn->error) $errors[] = 'Create schedules: ' . $conn->error;

$conn->query("CREATE TABLE IF NOT EXISTS cleanup_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    quantity INT DEFAULT 1,
    dust_blowing TINYINT(1) DEFAULT 0,
    rinse_detergent TINYINT(1) DEFAULT 0,
    dept_name VARCHAR(100),
    confirmed_by VARCHAR(100),
    signature_data MEDIUMTEXT,
    signature_type VARCHAR(10) DEFAULT 'drawn',
    cleaned_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($conn->error) $errors[] = 'Create cleanup_records: ' . $conn->error;

$conn->close();
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
                <p class="text-muted">Database <strong>monthly_cleanup</strong> and all tables are ready.</p>
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

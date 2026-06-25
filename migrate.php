<?php
// Connect without going through getDB() so we can show raw errors
$db = new mysqli('localhost', 'root', '', 'monthly_cleanup');
if ($db->connect_error) {
    die('<p style="font-family:sans-serif;color:red">Cannot connect: ' . htmlspecialchars($db->connect_error) . '</p>');
}
$db->set_charset('utf8mb4');

$steps = [];

// Check if column exists
$check = $db->query("SHOW COLUMNS FROM `schedules` LIKE 'dept_name'");
if ($check === false) {
    $steps[] = ['danger', 'SHOW COLUMNS failed: ' . $db->error];
} elseif ($check->num_rows > 0) {
    $steps[] = ['info', 'Column <strong>dept_name</strong> already exists in <em>schedules</em> — nothing to do.'];
} else {
    $db->query("ALTER TABLE `schedules` ADD COLUMN `dept_name` VARCHAR(100) DEFAULT NULL AFTER `scheduled_date`");
    if ($db->error) {
        $steps[] = ['danger', 'ALTER TABLE failed: ' . htmlspecialchars($db->error)];
    } else {
        $steps[] = ['success', 'Added column <strong>dept_name</strong> to <em>schedules</em> table.'];
    }
}

// Verify dept_name column
$verify = $db->query("SHOW COLUMNS FROM `schedules` LIKE 'dept_name'");
if ($verify && $verify->num_rows > 0) {
    $steps[] = ['success', 'Verified: column <strong>dept_name</strong> is present in <em>schedules</em>.'];
} else {
    $steps[] = ['warning', 'Verification failed — column may not have been created. Check MySQL user permissions.'];
}

// --- Migration 2: update unique key to include dept_name ---
$keyCheck = $db->query("SHOW INDEX FROM `schedules` WHERE Key_name = 'uq_schedule'");
if ($keyCheck && $keyCheck->num_rows > 0) {
    // Check if dept_name is already part of the key
    $inKey = false;
    while ($row = $keyCheck->fetch_assoc()) {
        if ($row['Column_name'] === 'dept_name') { $inKey = true; break; }
    }
    if ($inKey) {
        $steps[] = ['info', 'Unique key already includes <strong>dept_name</strong> — nothing to do.'];
    } else {
        $db->query("ALTER TABLE `schedules` DROP INDEX `uq_schedule`");
        if ($db->error) {
            $steps[] = ['danger', 'DROP INDEX failed: ' . htmlspecialchars($db->error)];
        } else {
            $db->query("ALTER TABLE `schedules` ADD UNIQUE KEY `uq_schedule` (company, equipment_type, scheduled_date, dept_name)");
            if ($db->error) {
                $steps[] = ['danger', 'ADD UNIQUE KEY failed: ' . htmlspecialchars($db->error)];
            } else {
                $steps[] = ['success', 'Updated unique key to include <strong>dept_name</strong> — same company/type/date now allowed for different departments.'];
            }
        }
    }
} else {
    // No key at all — create it fresh
    $db->query("ALTER TABLE `schedules` ADD UNIQUE KEY `uq_schedule` (company, equipment_type, scheduled_date, dept_name)");
    $steps[] = $db->error
        ? ['danger', 'ADD UNIQUE KEY failed: ' . htmlspecialchars($db->error)]
        : ['success', 'Created unique key on (company, equipment_type, scheduled_date, dept_name).'];
}

$db->close();
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
    <h5 class="mb-4">Database Migration</h5>
    <?php foreach ($steps as list($type, $msg)): ?>
    <div class="alert alert-<?= $type ?>"><?= $msg ?></div>
    <?php endforeach; ?>
    <a href="schedule.php" class="btn btn-primary">Go to Schedules &rarr;</a>
</div>
</body>
</html>

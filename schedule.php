<?php
require_once 'db.php';
$db = getDB();

$flash = '';
$flashType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $company   = $_POST['company'] ?? '';
        $type      = $_POST['equipment_type'] ?? '';
        $date      = $_POST['scheduled_date'] ?? '';
        $dept_name = trim($_POST['dept_name'] ?? '');

        if (in_array($company, ['GW','IND']) && in_array($type, ['PC','Printer']) && $date) {
            $stmt = $db->prepare("INSERT IGNORE INTO schedules (company, equipment_type, scheduled_date, dept_name) VALUES (?,?,?,?)");
            if ($stmt === false) {
                $flash = "DB error: " . $db->error . " — have you run <a href='migrate.php'>migrate.php</a>?";
                $flashType = 'danger';
            } else {
                $stmt->bind_param('ssss', $company, $type, $date, $dept_name);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $flash = "Schedule added: {$company}-{$type} on {$date}";
                } else {
                    $flash = "That schedule already exists or could not be added.";
                    $flashType = 'warning';
                }
            }
        } else {
            $flash = "Please fill in all fields.";
            $flashType = 'danger';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $flash = "Schedule deleted.";
        }
    }
}

// Fetch all schedules
$result = $db->query(
    "SELECT s.*, r.id AS record_id
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     ORDER BY s.scheduled_date DESC, s.company, s.equipment_type"
);
$schedules = $result->fetch_all(MYSQLI_ASSOC);

$page_title  = 'Schedules';
$active_page = 'schedule';
include 'header.php';
?>

<div class="container py-4" style="max-width:900px">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Add schedule form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Add Schedule
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Company</label>
                            <select name="company" class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="GW">GW</option>
                                <option value="IND">IND</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Equipment Type</label>
                            <select name="equipment_type" class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="PC">PC</option>
                                <option value="Printer">Printer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Scheduled Date</label>
                            <input type="date" name="scheduled_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Department</label>
                            <select name="dept_name" id="dept_name" class="form-select" required>
                                <option value="">— Select company first —</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-semibold text-muted mb-2">Quick Add — All 4 Groups</h6>
                    <p class="small text-muted">Add all 4 combos for one date at once:</p>
                    <form method="post" id="bulkForm">
                        <input type="hidden" name="action" value="bulk">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Date</label>
                            <input type="date" name="bulk_date" class="form-control form-control-sm" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            Add GW-PC, GW-Printer, IND-PC, IND-Printer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Schedules list -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="bi bi-list-ul me-1"></i>All Schedules
                    <span class="badge bg-secondary ms-2"><?= count($schedules) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($schedules)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                            No schedules yet. Add one using the form.
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Company</th>
                                    <th>Type</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($schedules as $s):
                                $done    = !empty($s['record_id']);
                                $overdue = !$done && $s['scheduled_date'] < date('Y-m-d');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($s['scheduled_date']) ?></td>
                                <td><span class="badge bg-dark"><?= htmlspecialchars($s['company']) ?></span></td>
                                <td>
                                    <?= $s['equipment_type'] === 'PC' ? '💻' : '🖨️' ?>
                                    <?= htmlspecialchars($s['equipment_type']) ?>
                                </td>
                                <td><?= $s['dept_name'] ? htmlspecialchars($s['dept_name']) : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <?php if ($done): ?>
                                        <span class="badge bg-success">Done</span>
                                    <?php elseif ($overdue): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="cleanup.php?id=<?= $s['id'] ?>"
                                       class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php if (!$done): ?>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this schedule?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$deptJson = json_encode(DEPARTMENTS, JSON_UNESCAPED_UNICODE);
$extra_js = <<<JS
<script>
const DEPARTMENTS = {$deptJson};

function updateDeptOptions(company, selectEl, currentVal) {
    selectEl.innerHTML = '<option value="">— Select —</option>';
    const list = DEPARTMENTS[company] || [];
    list.forEach(function(d) {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = d;
        if (d === currentVal) opt.selected = true;
        selectEl.appendChild(opt);
    });
}

const companySelect = document.querySelector('select[name="company"]');
const deptSelect    = document.getElementById('dept_name');

if (companySelect && deptSelect) {
    companySelect.addEventListener('change', function() {
        updateDeptOptions(this.value, deptSelect, '');
    });
}

document.getElementById('bulkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const date = this.querySelector('[name="bulk_date"]').value;
    if (!date) return;
    const groups = [
        ['GW','PC'],['GW','Printer'],['IND','PC'],['IND','Printer']
    ];
    (async () => {
        for (const [company, type] of groups) {
            const fd = new FormData();
            fd.append('action','add');
            fd.append('company', company);
            fd.append('equipment_type', type);
            fd.append('scheduled_date', date);
            await fetch('schedule.php', {method:'POST', body: fd});
        }
        window.location.reload();
    })();
});
</script>
JS;
include 'footer.php';
?>

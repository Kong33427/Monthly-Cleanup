<?php
require_once 'db.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Load schedule
$stmt = $db->prepare(
    "SELECT s.id, s.company, s.equipment_type, s.scheduled_date,
            s.dept_name AS schedule_dept,
            r.id AS record_id, r.quantity, r.dust_blowing, r.rinse_detergent,
            r.dept_name AS record_dept, r.confirmed_by, r.signature_data, r.signature_type,
            r.cleaned_date, r.notes, r.created_at AS completed_at
     FROM schedules s
     LEFT JOIN cleanup_records r ON r.schedule_id = s.id
     WHERE s.id = ?"
);
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: index.php');
    exit;
}

$flash = '';
$flashType = 'success';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($schedule['record_id'])) {
    $quantity         = max(1, (int)($_POST['quantity'] ?? 1));
    $dust_blowing     = isset($_POST['dust_blowing'])    ? 1 : 0;
    $rinse_detergent  = isset($_POST['rinse_detergent']) ? 1 : 0;
    $dept_name        = trim($_POST['dept_name'] ?? '');
    $confirmed_by     = trim($_POST['confirmed_by'] ?? '');
    $cleaned_date     = $_POST['cleaned_date'] ?? date('Y-m-d');
    $notes            = trim($_POST['notes'] ?? '');
    $signature_data   = $_POST['signature_data'] ?? '';
    $signature_type   = $_POST['signature_type'] ?? 'drawn';

    // Handle file upload
    if (empty($signature_data) && isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/png','image/jpeg','image/gif','image/webp'];
        $mime    = mime_content_type($_FILES['signature_file']['tmp_name']);
        if (in_array($mime, $allowed)) {
            $raw = file_get_contents($_FILES['signature_file']['tmp_name']);
            $signature_data = 'data:' . $mime . ';base64,' . base64_encode($raw);
            $signature_type = 'uploaded';
        }
    }

    $errors = [];
    if (!$dept_name)     $errors[] = 'Department name is required.';
    if (!$confirmed_by)  $errors[] = 'Confirmed by is required.';
    if (!$signature_data) $errors[] = 'Signature is required.';

    if (empty($errors)) {
        $ins = $db->prepare(
            "INSERT INTO cleanup_records
             (schedule_id, quantity, dust_blowing, rinse_detergent, dept_name, confirmed_by,
              signature_data, signature_type, cleaned_date, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        try {
            $ins->execute([
                $id, $quantity, $dust_blowing, $rinse_detergent,
                $dept_name, $confirmed_by,
                $signature_data, $signature_type, $cleaned_date, $notes
            ]);
            header("Location: cleanup.php?id={$id}&saved=1");
            exit;
        } catch (PDOException $e) {
            $flash = "Save failed: " . htmlspecialchars($e->getMessage());
            $flashType = 'danger';
        }
    } else {
        $flash = implode(' ', $errors);
        $flashType = 'danger';
    }
}

if (isset($_GET['saved'])) {
    // Reload to get record
    $stmt->execute([$id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    $flash = 'Cleanup recorded successfully!';
}

$isDone  = !empty($schedule['record_id']);
$company = htmlspecialchars($schedule['company']);
$etype   = htmlspecialchars($schedule['equipment_type']);
$icon    = $schedule['equipment_type'] === 'PC' ? '💻' : '🖨️';

$page_title  = "{$company}-{$etype} Cleanup";
$active_page = '';
include 'header.php';
?>

<div class="container py-4" style="max-width:760px">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="no-print">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Calendar</a></li>
            <li class="breadcrumb-item active"><?= $company ?>-<?= $etype ?> — <?= htmlspecialchars($schedule['scheduled_date']) ?></li>
        </ol>
    </nav>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Header card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex align-items-center gap-3 py-3">
            <div class="fs-2"><?= $icon ?></div>
            <div>
                <h5 class="mb-0 fw-bold"><?= $company ?>-<?= $etype ?> Cleanup</h5>
                <div class="text-muted small">
                    Scheduled: <strong><?= htmlspecialchars($schedule['scheduled_date']) ?></strong>
                </div>
            </div>
            <div class="ms-auto">
                <?php if ($isDone): ?>
                    <span class="badge bg-success fs-6">&#x2713; Completed</span>
                <?php elseif ($schedule['scheduled_date'] < date('Y-m-d')): ?>
                    <span class="badge bg-danger fs-6">Overdue</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6">Pending</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($isDone): ?>
    <!-- Completed record view -->
    <div class="card shadow-sm">
        <div class="card-header fw-semibold bg-success text-white">
            <i class="bi bi-check-circle-fill me-1"></i>Cleanup Record
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label text-muted small">Quantity Cleaned</label>
                    <div class="fw-semibold"><?= (int)$schedule['quantity'] ?> unit(s)</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label text-muted small">Cleaned Date</label>
                    <div class="fw-semibold"><?= htmlspecialchars($schedule['cleaned_date']) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label text-muted small">Department</label>
                    <div class="fw-semibold"><?= htmlspecialchars($schedule['record_dept']) ?></div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label text-muted small">Confirmed By</label>
                    <div class="fw-semibold"><?= htmlspecialchars($schedule['confirmed_by']) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Cleaning Steps</label>
                    <div class="d-flex gap-3">
                        <span class="badge <?= $schedule['dust_blowing']    ? 'bg-success' : 'bg-secondary' ?> fs-6">
                            <?= $schedule['dust_blowing'] ? '&#x2713;' : '&#x2715;' ?> Dust Blowing
                        </span>
                        <span class="badge <?= $schedule['rinse_detergent'] ? 'bg-success' : 'bg-secondary' ?> fs-6">
                            <?= $schedule['rinse_detergent'] ? '&#x2713;' : '&#x2715;' ?> Rinse w/ Detergent
                        </span>
                    </div>
                </div>
                <?php if ($schedule['notes']): ?>
                <div class="col-12">
                    <label class="form-label text-muted small">Notes</label>
                    <div><?= nl2br(htmlspecialchars($schedule['notes'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($schedule['signature_data']): ?>
                <div class="col-12">
                    <label class="form-label text-muted small">Signature</label>
                    <div class="border rounded p-2 bg-white" style="max-width:320px">
                        <img src="<?= $schedule['signature_data'] ?>" alt="Signature"
                             style="max-width:100%;max-height:120px;display:block">
                    </div>
                    <div class="text-muted small mt-1">
                        Recorded: <?= htmlspecialchars($schedule['completed_at']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3 no-print">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Print / PDF
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Back to Calendar</a>
    </div>

    <?php else: ?>
    <!-- Cleanup form -->
    <form method="post" enctype="multipart/form-data" id="cleanupForm">

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-list-check me-1"></i>Cleaning Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Quantity Cleaned <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control"
                               min="1" value="1" required>
                        <div class="form-text">Number of <?= $etype ?>s cleaned</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Cleaned Date <span class="text-danger">*</span></label>
                        <input type="date" name="cleaned_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Cleaning Steps</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="dust_blowing" id="dust" checked>
                                <label class="form-check-label" for="dust">
                                    <i class="bi bi-wind text-info"></i> Dust Blowing
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="rinse_detergent" id="rinse" checked>
                                <label class="form-check-label" for="rinse">
                                    <i class="bi bi-droplet-half text-primary"></i> Rinse with Detergent
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <?php
                        $co       = $schedule['company'];
                        $deptList = DEPARTMENTS[$co] ?? [];
                        $preFill  = $schedule['schedule_dept'] ?? '';
                        ?>
                        <select name="dept_name" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach ($deptList as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>"
                                <?= $d === $preFill ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Confirmed By <span class="text-danger">*</span></label>
                        <input type="text" name="confirmed_by" class="form-control"
                               placeholder="Full name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature section -->
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-pen me-1"></i>Department Signature <span class="text-danger">*</span>
            </div>
            <div class="card-body">

                <ul class="nav nav-tabs mb-3" id="sigTabs">
                    <li class="nav-item">
                        <button class="nav-link active" type="button" id="tabDraw"
                                onclick="switchTab('draw')">
                            <i class="bi bi-pen"></i> Draw
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" id="tabUpload"
                                onclick="switchTab('upload')">
                            <i class="bi bi-upload"></i> Upload Image
                        </button>
                    </li>
                </ul>

                <!-- Draw panel -->
                <div id="panelDraw">
                    <div class="sig-canvas-wrapper border rounded bg-white mb-2" style="max-width:500px">
                        <canvas id="signature-canvas" width="500" height="160"></canvas>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="sigPad.clear()">
                        <i class="bi bi-eraser"></i> Clear
                    </button>
                    <span class="text-muted small ms-2">Sign inside the box above</span>
                </div>

                <!-- Upload panel -->
                <div id="panelUpload" class="d-none">
                    <input type="file" name="signature_file" id="signature_file"
                           class="form-control" accept="image/*" style="max-width:400px">
                    <div class="form-text">Accepted: PNG, JPG, GIF, WebP</div>
                    <div id="uploadPreview" class="mt-2 d-none">
                        <img id="uploadPreviewImg" src="" alt="Preview"
                             style="max-width:300px;max-height:120px;border:1px solid #dee2e6;border-radius:4px">
                    </div>
                </div>

                <input type="hidden" name="signature_data" id="signature_data">
                <input type="hidden" name="signature_type" id="signature_type" value="drawn">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success btn-lg px-4" id="submitBtn">
                <i class="bi bi-check-circle-fill me-1"></i>Save Cleanup Record
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
        </div>

    </form>
    <?php endif; ?>

</div>

<?php
$extra_js = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
const canvas  = document.getElementById('signature-canvas');
const sigPad  = canvas ? new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' }) : null;
let   drawMode = true;

function resizeCanvas() {
    if (!canvas) return;
    const ratio  = Math.max(window.devicePixelRatio || 1, 1);
    const wrapper = canvas.parentElement;
    canvas.width  = wrapper.offsetWidth * ratio;
    canvas.height = 160 * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    canvas.style.width  = wrapper.offsetWidth + 'px';
    canvas.style.height = '160px';
    sigPad.clear();
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

function switchTab(mode) {
    drawMode = (mode === 'draw');
    document.getElementById('panelDraw').classList.toggle('d-none',   !drawMode);
    document.getElementById('panelUpload').classList.toggle('d-none',  drawMode);
    document.getElementById('tabDraw').classList.toggle('active',   drawMode);
    document.getElementById('tabUpload').classList.toggle('active', !drawMode);
    document.getElementById('signature_type').value = drawMode ? 'drawn' : 'uploaded';
}

// Upload preview
const fileInput = document.getElementById('signature_file');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('uploadPreviewImg').src = e.target.result;
            document.getElementById('uploadPreview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
}

// Form submit validation
const form = document.getElementById('cleanupForm');
if (form) {
    form.addEventListener('submit', function(e) {
        if (drawMode) {
            if (!sigPad || sigPad.isEmpty()) {
                e.preventDefault();
                alert('Please draw your signature before submitting.');
                return;
            }
            document.getElementById('signature_data').value = sigPad.toDataURL('image/png');
            document.getElementById('signature_type').value = 'drawn';
        } else {
            const fi = document.getElementById('signature_file');
            if (!fi || !fi.files.length) {
                e.preventDefault();
                alert('Please upload a signature image before submitting.');
                return;
            }
            // signature_data will be read by PHP from the file
            document.getElementById('signature_data').value = '';
        }
    });
}
</script>
JS;
include 'footer.php';
?>

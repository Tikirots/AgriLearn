<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_admin();

$program_id = (int)($_GET['program_id'] ?? 0);

// ---- NEW: if no program is selected (e.g. clicked "Modules" in the
// sidebar with no program_id in the URL), show a picker instead of
// immediately failing with "Program not found."
if (!$program_id) {
    $all_programs = $pdo->query("
        SELECT p.*, (SELECT COUNT(*) FROM modules m WHERE m.program_id=p.id) AS module_count
        FROM training_programs p
        ORDER BY p.created_at DESC
    ")->fetchAll();

    $page_title = 'Modules';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <h3 class="mb-1"><i class="fa-solid fa-book"></i> Modules</h3>
    <p class="text-muted mb-3">Choose a training program to manage its modules.</p>

    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Program</th><th>NC Level</th><th>Modules</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($all_programs as $p): ?>
            <tr>
              <td class="fw-semibold"><?= clean($p['title']) ?></td>
              <td><?= clean($p['nc_level']) ?></td>
              <td><?= (int)$p['module_count'] ?></td>
              <td><span class="badge bg-<?= $p['status']==='open'?'success':($p['status']==='ongoing'?'warning':'secondary') ?>"><?= ucfirst($p['status']) ?></span></td>
              <td>
                <a href="?program_id=<?= $p['id'] ?>" class="btn btn-sm btn-success">
                  <i class="fa-solid fa-book"></i> Manage Modules
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$all_programs): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">No training programs yet. <a href="<?= BASE_URL ?>/admin/programs.php">Create one first</a>.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM training_programs WHERE id=?");
$stmt->execute([$program_id]);
$program = $stmt->fetch();
if (!$program) { set_flash('danger', 'Program not found.'); redirect('/admin/programs.php'); }

// Upload module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['module_file'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $order_num = (int)$_POST['order_num'];

    $file = $_FILES['module_file'];
    $allowed = ['pdf','ppt','pptx','doc','docx','mp4'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Upload failed.');
    } elseif (!in_array($ext, $allowed)) {
        set_flash('danger', 'Invalid file type. Allowed: ' . implode(', ', $allowed));
    } else {
        $safe_name = uniqid('mod_') . '.' . $ext;
        $dest = UPLOAD_MODULES_DIR . $safe_name;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $pdo->prepare("INSERT INTO modules (program_id, title, description, file_path, order_num, is_visible) VALUES (?,?,?,?,?,0)");
            $stmt->execute([$program_id, $title, $description, $safe_name, $order_num]);
            set_flash('success', 'Module uploaded. It is hidden by default — toggle visibility when ready.');
        } else {
            set_flash('danger', 'Could not save uploaded file.');
        }
    }
    redirect('/admin/modules.php?program_id=' . $program_id);
}

// Toggle visibility
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("SELECT is_visible, title FROM modules WHERE id=? AND program_id=?");
    $stmt->execute([$_GET['toggle'], $program_id]);
    $mod = $stmt->fetch();

    if ($mod) {
        $stmt = $pdo->prepare("UPDATE modules SET is_visible = NOT is_visible WHERE id=? AND program_id=?");
        $stmt->execute([$_GET['toggle'], $program_id]);

        // Only fire notifications when the module is going hidden -> visible
        if ((int)$mod['is_visible'] === 0) {
            $admin = current_user();
            notify_module_published($pdo, $program_id, $mod['title'], $admin['id']);
            set_flash('success', 'Module released — trainees have been notified.');
        } else {
            set_flash('success', 'Module hidden from trainees.');
        }
    }
    redirect('/admin/modules.php?program_id=' . $program_id);
}

// Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT file_path FROM modules WHERE id=? AND program_id=?");
    $stmt->execute([$_GET['delete'], $program_id]);
    if ($m = $stmt->fetch()) {
        @unlink(UPLOAD_MODULES_DIR . $m['file_path']);
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        set_flash('success', 'Module deleted.');
    }
    redirect('/admin/modules.php?program_id=' . $program_id);
}

$stmt = $pdo->prepare("SELECT * FROM modules WHERE program_id=? ORDER BY order_num ASC, uploaded_at ASC");
$stmt->execute([$program_id]);
$modules = $stmt->fetchAll();

$page_title = 'Modules - ' . $program['title'];
require_once __DIR__ . '/../includes/header.php';
?>
<a href="<?= BASE_URL ?>/admin/modules.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-arrow-left"></i> All Programs</a>
<h3 class="mb-1"><i class="fa-solid fa-book"></i> Modules &mdash; <?= clean($program['title']) ?></h3>
<p class="text-muted mb-3">Upload the complete module, then control which topics are visible to trainees based on the current lesson or schedule. Making a module visible notifies every approved trainee in this program.</p>

<div class="row">
  <div class="col-lg-4 mb-3">
    <div class="card border-0 shadow-sm p-3">
      <h6 class="fw-semibold mb-3">Upload New Module</h6>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
        <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">Order</label><input type="number" class="form-control" name="order_num" value="<?= count($modules)+1 ?>"></div>
        <div class="mb-3"><label class="form-label">File (PDF, PPT, DOC, MP4)</label><input type="file" class="form-control" name="module_file" required></div>
        <button class="btn btn-success w-100"><i class="fa-solid fa-upload"></i> Upload</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>#</th><th>Title</th><th>File</th><th>Visibility</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($modules as $m): ?>
            <tr>
              <td><?= (int)$m['order_num'] ?></td>
              <td><?= clean($m['title']) ?><br><small class="text-muted"><?= clean($m['description']) ?></small></td>
              <td><?= clean($m['file_path']) ?></td>
              <td>
                <a href="?program_id=<?= $program_id ?>&toggle=<?= $m['id'] ?>" class="badge text-decoration-none bg-<?= $m['is_visible']?'success':'secondary' ?>" onclick="return confirm('<?= $m['is_visible'] ? 'Hide this module from trainees?' : 'Release this module? All approved trainees will be notified.' ?>')">
                  <i class="fa-solid fa-<?= $m['is_visible']?'eye':'eye-slash' ?>"></i> <?= $m['is_visible']?'Visible':'Hidden' ?>
                </a>
              </td>
              <td>
                <a href="?program_id=<?= $program_id ?>&delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this module?')"><i class="fa-solid fa-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$modules): ?><tr><td colspan="5" class="text-center text-muted py-3">No modules uploaded yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
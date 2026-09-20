<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

$module_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT m.*, p.title AS program_title FROM modules m JOIN training_programs p ON p.id=m.program_id WHERE m.id=? AND m.is_visible=1");
$stmt->execute([$module_id]);
$module = $stmt->fetch();

if (!$module) { set_flash('danger', 'Module not found or not currently visible.'); redirect('/trainee/modules.php'); }

// Confirm trainee is approved for this program
$stmt = $pdo->prepare("SELECT id FROM enrollments WHERE trainee_id=? AND program_id=? AND status IN ('approved','completed')");
$stmt->execute([$trainee['id'], $module['program_id']]);
if (!$stmt->fetch()) { set_flash('danger', 'You are not enrolled in this program.'); redirect('/trainee/modules.php'); }

// Log the view as an activity (once per session per module to avoid spam)
$logKey = 'viewed_module_' . $module_id;
if (empty($_SESSION[$logKey])) {
    $stmt = $pdo->prepare("INSERT INTO trainee_activities (trainee_id, program_id, module_id, activity_type, description) VALUES (?,?,?, 'Module Viewed', ?)");
    $stmt->execute([$trainee['id'], $module['program_id'], $module_id, $module['title']]);
    $_SESSION[$logKey] = true;
}

$ext = strtolower(pathinfo($module['file_path'], PATHINFO_EXTENSION));
$fileUrl = BASE_URL . '/trainee/module_stream.php?id=' . $module_id;

$page_title = $module['title'];
require_once __DIR__ . '/../includes/header.php';
?>
<a href="<?= BASE_URL ?>/trainee/modules.php?program_id=<?= $module['program_id'] ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-arrow-left"></i> Back to Modules</a>
<h4><?= clean($module['title']) ?></h4>
<p class="text-muted small"><?= clean($module['program_title']) ?></p>

<div class="module-viewer card border-0 shadow-sm p-2" oncontextmenu="return false;">
  <?php if ($ext === 'pdf'): ?>
    <iframe src="<?= $fileUrl ?>#toolbar=0" class="w-100" style="height:80vh;border:0;"></iframe>
  <?php elseif ($ext === 'mp4'): ?>
    <video src="<?= $fileUrl ?>" class="w-100" controls controlsList="nodownload noremoteplayback" style="max-height:80vh;" oncontextmenu="return false;"></video>
  <?php else: ?>
    <div class="text-center py-5">
      <i class="fa-solid fa-file-lines fa-3x text-success mb-3"></i>
      <p>This file type (<?= clean($ext) ?>) can only be previewed within the system for security. Please ask your trainer if you need an alternate viewing option.</p>
      <iframe src="https://docs.google.com/gview?url=<?= urlencode(SITE_URL . '/trainee/module_stream.php?id=' . $module_id) ?>&embedded=true" class="w-100" style="height:75vh;border:0;"></iframe>
    </div>
  <?php endif; ?>
</div>
<p class="small text-muted mt-2"><i class="fa-solid fa-lock"></i> Downloading, copying, and screenshotting are disabled where supported by your device.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

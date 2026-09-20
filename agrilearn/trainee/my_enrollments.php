<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

$stmt = $pdo->prepare("SELECT e.*, p.title, p.description, p.start_date, p.end_date, p.nc_level FROM enrollments e JOIN training_programs p ON p.id=e.program_id WHERE e.trainee_id=? ORDER BY e.enrolled_at DESC");
$stmt->execute([$trainee['id']]);
$enrollments = $stmt->fetchAll();

$page_title = 'My Programs';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-list-check"></i> My Programs</h3>
<div class="row g-3">
  <?php foreach ($enrollments as $e): ?>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm p-3">
        <div class="d-flex justify-content-between">
          <h5><?= clean($e['title']) ?></h5>
          <span class="badge bg-<?= ['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'secondary'][$e['status']] ?>"><?= ucfirst($e['status']) ?></span>
        </div>
        <p class="text-muted small"><?= clean($e['nc_level']) ?></p>
        <p class="small"><?= clean($e['description']) ?></p>
        <p class="small mb-0"><i class="fa-regular fa-calendar"></i> <?= format_date($e['start_date']) ?> &ndash; <?= format_date($e['end_date']) ?></p>
        <?php if ($e['status']==='approved'): ?>
          <a href="<?= BASE_URL ?>/trainee/modules.php?program_id=<?= $e['program_id'] ?>" class="btn btn-sm btn-success mt-3">View Modules</a>
        <?php elseif ($e['status']==='completed'): ?>
          <a href="<?= BASE_URL ?>/trainee/certificate.php" class="btn btn-sm btn-outline-success mt-3">View Certificate</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$enrollments): ?><div class="col-12"><p class="text-muted text-center py-4">No enrollments yet. <a href="<?= BASE_URL ?>/trainee/enroll.php">Browse programs</a>.</p></div><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

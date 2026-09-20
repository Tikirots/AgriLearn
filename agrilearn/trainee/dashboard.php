<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

$stmt = $pdo->prepare("SELECT e.*, p.title, p.start_date, p.end_date FROM enrollments e JOIN training_programs p ON p.id=e.program_id WHERE e.trainee_id=? ORDER BY e.enrolled_at DESC");
$stmt->execute([$trainee['id']]);
$enrollments = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM certificates WHERE trainee_id=?");
$stmt->execute([$trainee['id']]);
$cert_count = $stmt->fetch()['c'];

$page_title = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-1">Welcome, <?= clean($trainee['full_name']) ?> <i class="fa-solid fa-seedling text-success"></i></h3>
<p class="text-muted mb-4"><?= CENTER_NAME ?></p>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-list-check fa-lg text-success mb-2"></i>
      <h4><?= count($enrollments) ?></h4><small class="text-muted">Total Enrollments</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-certificate fa-lg text-success mb-2"></i>
      <h4><?= $cert_count ?></h4><small class="text-muted">Certificates</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?= BASE_URL ?>/trainee/enroll.php" class="text-decoration-none">
      <div class="card border-0 shadow-sm text-center p-3 quick-link h-100 d-flex justify-content-center">
        <i class="fa-solid fa-pen-to-square fa-lg text-success mb-2"></i><span>Enroll in a Program</span>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?= BASE_URL ?>/trainee/modules.php" class="text-decoration-none">
      <div class="card border-0 shadow-sm text-center p-3 quick-link h-100 d-flex justify-content-center">
        <i class="fa-solid fa-book-open fa-lg text-success mb-2"></i><span>View Learning Modules</span>
      </div>
    </a>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">My Enrollments</div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light"><tr><th>Program</th><th>Schedule</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><?= clean($e['title']) ?></td>
          <td><?= format_date($e['start_date']) ?> &ndash; <?= format_date($e['end_date']) ?></td>
          <td><span class="badge bg-<?= ['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'secondary'][$e['status']] ?>"><?= ucfirst($e['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$enrollments): ?><tr><td colspan="3" class="text-center text-muted py-3">You haven't enrolled in any program yet. <a href="<?= BASE_URL ?>/trainee/enroll.php">Enroll now</a>.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

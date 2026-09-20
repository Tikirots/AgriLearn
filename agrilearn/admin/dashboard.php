<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$counts = [
    'trainees'    => $pdo->query("SELECT COUNT(*) c FROM trainees")->fetch()['c'],
    'programs'    => $pdo->query("SELECT COUNT(*) c FROM training_programs")->fetch()['c'],
    'pending_acct'=> $pdo->query("SELECT COUNT(*) c FROM users WHERE role='trainee' AND status='pending'")->fetch()['c'],
    'pending'     => $pdo->query("SELECT COUNT(*) c FROM enrollments WHERE status='pending'")->fetch()['c'],
    'approved'    => $pdo->query("SELECT COUNT(*) c FROM enrollments WHERE status='approved'")->fetch()['c'],
    'certificates'=> $pdo->query("SELECT COUNT(*) c FROM certificates")->fetch()['c'],
];

$recent_activities = $pdo->query("
    SELECT a.*, t.full_name, p.title AS program_title
    FROM trainee_activities a
    JOIN trainees t ON t.id = a.trainee_id
    JOIN training_programs p ON p.id = a.program_id
    ORDER BY a.activity_date DESC LIMIT 8
")->fetchAll();

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-4"><i class="fa-solid fa-gauge"></i> Admin Dashboard</h3>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-users fa-lg text-success mb-2"></i>
      <h4><?= $counts['trainees'] ?></h4><small class="text-muted">Trainees</small>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-layer-group fa-lg text-success mb-2"></i>
      <h4><?= $counts['programs'] ?></h4><small class="text-muted">Programs</small>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <a href="<?= BASE_URL ?>/admin/trainees.php" class="text-decoration-none">
      <div class="card stat-card border-0 shadow-sm text-center p-3">
        <i class="fa-solid fa-user-clock fa-lg text-danger mb-2"></i>
        <h4><?= $counts['pending_acct'] ?></h4><small class="text-muted">Pending Registrations</small>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-hourglass-half fa-lg text-warning mb-2"></i>
      <h4><?= $counts['pending'] ?></h4><small class="text-muted">Pending Enrollments</small>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-user-check fa-lg text-success mb-2"></i>
      <h4><?= $counts['approved'] ?></h4><small class="text-muted">Approved</small>
    </div>
  </div>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card stat-card border-0 shadow-sm text-center p-3">
      <i class="fa-solid fa-certificate fa-lg text-success mb-2"></i>
      <h4><?= $counts['certificates'] ?></h4><small class="text-muted">Certificates Issued</small>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <a href="<?= BASE_URL ?>/admin/enrollments.php" class="text-decoration-none">
      <div class="card border-0 shadow-sm p-3 quick-link"><i class="fa-solid fa-user-check text-success"></i> Review Pending Enrollments</div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="<?= BASE_URL ?>/admin/programs.php" class="text-decoration-none">
      <div class="card border-0 shadow-sm p-3 quick-link"><i class="fa-solid fa-plus text-success"></i> Manage Training Programs</div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="<?= BASE_URL ?>/admin/certificates.php" class="text-decoration-none">
      <div class="card border-0 shadow-sm p-3 quick-link"><i class="fa-solid fa-certificate text-success"></i> Generate Certificates</div>
    </a>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">Recent Trainee Activity</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th>Trainee</th><th>Program</th><th>Activity</th><th>Date</th></tr></thead>
      <tbody>
        <?php if (!$recent_activities): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">No activity recorded yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent_activities as $a): ?>
          <tr>
            <td><?= clean($a['full_name']) ?></td>
            <td><?= clean($a['program_title']) ?></td>
            <td><span class="badge bg-success-subtle text-success-emphasis"><?= clean($a['activity_type']) ?></span> <?= clean($a['description']) ?></td>
            <td><?= date('M j, Y g:i A', strtotime($a['activity_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

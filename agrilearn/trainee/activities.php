<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

$stmt = $pdo->prepare("SELECT a.*, p.title AS program_title FROM trainee_activities a JOIN training_programs p ON p.id=a.program_id WHERE a.trainee_id=? ORDER BY a.activity_date DESC");
$stmt->execute([$trainee['id']]);
$activities = $stmt->fetchAll();

$page_title = 'My Activities';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-chart-line"></i> My Activity Log</h3>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light"><tr><th>Program</th><th>Activity</th><th>Notes</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($activities as $a): ?>
        <tr>
          <td><?= clean($a['program_title']) ?></td>
          <td><span class="badge bg-success-subtle text-success-emphasis"><?= clean($a['activity_type']) ?></span></td>
          <td><?= clean($a['description']) ?></td>
          <td><?= date('M j, Y g:i A', strtotime($a['activity_date'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$activities): ?><tr><td colspan="4" class="text-center text-muted py-3">No activity recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainee_id = (int)$_POST['trainee_id'];
    $program_id = (int)$_POST['program_id'];
    $activity_type = trim($_POST['activity_type']);
    $description = trim($_POST['description']);
    $stmt = $pdo->prepare("INSERT INTO trainee_activities (trainee_id, program_id, activity_type, description) VALUES (?,?,?,?)");
    $stmt->execute([$trainee_id, $program_id, $activity_type, $description]);
    set_flash('success', 'Activity recorded.');
    redirect('/admin/activities.php');
}

$approved = $pdo->query("
    SELECT e.trainee_id, e.program_id, t.full_name, p.title
    FROM enrollments e
    JOIN trainees t ON t.id=e.trainee_id
    JOIN training_programs p ON p.id=e.program_id
    WHERE e.status IN ('approved','completed')
    ORDER BY t.full_name
")->fetchAll();

$activities = $pdo->query("
    SELECT a.*, t.full_name, p.title AS program_title
    FROM trainee_activities a
    JOIN trainees t ON t.id=a.trainee_id
    JOIN training_programs p ON p.id=a.program_id
    ORDER BY a.activity_date DESC LIMIT 100
")->fetchAll();

$page_title = 'Trainee Activities';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-clipboard-list"></i> Trainee Activity Recording</h3>

<div class="row">
  <div class="col-lg-4 mb-3">
    <div class="card border-0 shadow-sm p-3">
      <h6 class="fw-semibold mb-3">Record New Activity</h6>
      <form method="POST">
        <div class="mb-2">
          <label class="form-label">Trainee / Program</label>
          <select name="trainee_program" class="form-select" required onchange="const [t,p]=this.value.split('|');document.getElementById('tid').value=t;document.getElementById('pid').value=p;">
            <option value="">Select trainee</option>
            <?php foreach ($approved as $a): ?>
              <option value="<?= $a['trainee_id'] ?>|<?= $a['program_id'] ?>"><?= clean($a['full_name']) ?> &mdash; <?= clean($a['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="trainee_id" id="tid">
          <input type="hidden" name="program_id" id="pid">
        </div>
        <div class="mb-2">
          <label class="form-label">Activity Type</label>
          <select class="form-select" name="activity_type">
            <option>Attendance</option>
            <option>Module Viewed</option>
            <option>Practical Exercise</option>
            <option>Assessment</option>
            <option>Other</option>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="description" rows="2"></textarea></div>
        <button class="btn btn-success w-100"><i class="fa-solid fa-plus"></i> Record</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Trainee</th><th>Program</th><th>Activity</th><th>Notes</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($activities as $a): ?>
            <tr>
              <td><?= clean($a['full_name']) ?></td>
              <td><?= clean($a['program_title']) ?></td>
              <td><span class="badge bg-success-subtle text-success-emphasis"><?= clean($a['activity_type']) ?></span></td>
              <td><?= clean($a['description']) ?></td>
              <td><?= date('M j, Y g:i A', strtotime($a['activity_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$activities): ?><tr><td colspan="5" class="text-center text-muted py-3">No activity recorded yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

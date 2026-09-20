<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if (isset($_GET['action'], $_GET['id'])) {
    $status = $_GET['action'] === 'approve' ? 'approved' : ($_GET['action'] === 'reject' ? 'rejected' : ($_GET['action'] === 'complete' ? 'completed' : null));
    if ($status) {
        $stmt = $pdo->prepare("UPDATE enrollments SET status=?, decided_at=NOW() WHERE id=?");
        $stmt->execute([$status, $_GET['id']]);
        set_flash('success', "Enrollment marked as {$status}.");
    }
    redirect('/admin/enrollments.php');
}

$filter = $_GET['status'] ?? 'pending';
$sql = "SELECT e.*, t.full_name, t.contact_number, p.title AS program_title
        FROM enrollments e
        JOIN trainees t ON t.id = e.trainee_id
        JOIN training_programs p ON p.id = e.program_id";
if ($filter !== 'all') $sql .= " WHERE e.status = " . $pdo->quote($filter);
$sql .= " ORDER BY e.enrolled_at DESC";
$enrollments = $pdo->query($sql)->fetchAll();

$page_title = 'Enrollments';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-user-check"></i> Enrollment Applications</h3>

<div class="btn-group mb-3">
  <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','completed'=>'Completed','all'=>'All'] as $k=>$label): ?>
    <a href="?status=<?= $k ?>" class="btn btn-sm btn-outline-success <?= $filter===$k?'active':'' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th>Trainee</th><th>Contact</th><th>Program</th><th>Applied</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><?= clean($e['full_name']) ?></td>
          <td><?= clean($e['contact_number']) ?></td>
          <td><?= clean($e['program_title']) ?></td>
          <td><?= date('M j, Y', strtotime($e['enrolled_at'])) ?></td>
          <td><span class="badge bg-<?= ['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'secondary'][$e['status']] ?>"><?= ucfirst($e['status']) ?></span></td>
          <td>
            <?php if ($e['status']==='pending'): ?>
              <a href="?action=approve&id=<?= $e['id'] ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> Approve</a>
              <a href="?action=reject&id=<?= $e['id'] ?>" class="btn btn-sm btn-danger"><i class="fa-solid fa-xmark"></i> Reject</a>
            <?php elseif ($e['status']==='approved'): ?>
              <a href="?action=complete&id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Mark this trainee as completed the program?')"><i class="fa-solid fa-flag-checkered"></i> Mark Completed</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$enrollments): ?><tr><td colspan="6" class="text-center text-muted py-3">No records found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_admin();

// Approve / reject / reactivate a trainee account
if (isset($_GET['status_action'], $_GET['user_id'])) {
    $new_status = ['approve' => 'active', 'reject' => 'inactive', 'reactivate' => 'active'][$_GET['status_action']] ?? null;
    if ($new_status) {
        $stmt = $pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='trainee'");
        $stmt->execute([$new_status, $_GET['user_id']]);

        if (in_array($_GET['status_action'], ['approve', 'reject'])) {
            notify_trainee_approval_status($pdo, (int)$_GET['user_id'], $_GET['status_action'] === 'approve');
        }
        set_flash('success', 'Account status updated.');
    }
    redirect('/admin/trainees.php' . (isset($_GET['view']) ? '?view=' . $_GET['view'] : ''));
}

if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.email, u.status AS acct_status FROM trainees t JOIN users u ON u.id=t.user_id WHERE t.id=?");
    $stmt->execute([$_GET['view']]);
    $trainee = $stmt->fetch();
    if (!$trainee) redirect('/admin/trainees.php');

    $stmt = $pdo->prepare("SELECT e.*, p.title FROM enrollments e JOIN training_programs p ON p.id=e.program_id WHERE e.trainee_id=?");
    $stmt->execute([$trainee['id']]);
    $enrollments = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT a.*, p.title AS program_title FROM trainee_activities a JOIN training_programs p ON p.id=a.program_id WHERE a.trainee_id=? ORDER BY a.activity_date DESC LIMIT 20");
    $stmt->execute([$trainee['id']]);
    $activities = $stmt->fetchAll();

    $page_title = 'Trainee Record';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <a href="<?= BASE_URL ?>/admin/trainees.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <div class="row">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 text-center">
          <?php if (!empty($trainee['photo'])): ?>
            <img src="<?= BASE_URL ?>/assets/uploads/trainees/<?= clean($trainee['photo']) ?>"
                 alt="Profile photo"
                 class="rounded-circle mx-auto mb-2"
                 style="width:110px;height:110px;object-fit:cover;">
          <?php else: ?>
            <i class="fa-solid fa-circle-user fa-4x text-success mb-2"></i>
          <?php endif; ?>
          <h5><?= clean($trainee['full_name']) ?></h5>
          <p class="text-muted mb-1"><?= clean($trainee['email']) ?></p>
          <span class="badge bg-<?= ['pending'=>'warning','active'=>'success','inactive'=>'secondary'][$trainee['acct_status']] ?>"><?= ucfirst($trainee['acct_status']) ?></span>
          <?php if ($trainee['acct_status'] === 'pending'): ?>
            <div class="mt-3">
              <a href="?view=<?= $trainee['id'] ?>&status_action=approve&user_id=<?= $trainee['user_id'] ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> Approve</a>
              <a href="?view=<?= $trainee['id'] ?>&status_action=reject&user_id=<?= $trainee['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this registration?')"><i class="fa-solid fa-xmark"></i> Reject</a>
            </div>
          <?php elseif ($trainee['acct_status'] === 'inactive'): ?>
            <div class="mt-3">
              <a href="?view=<?= $trainee['id'] ?>&status_action=reactivate&user_id=<?= $trainee['user_id'] ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-rotate-left"></i> Reactivate</a>
            </div>
          <?php endif; ?>
        </div>
        <div class="card border-0 shadow-sm p-3 mt-3">
          <p><strong>Username:</strong> <?= clean($trainee['username']) ?></p>
          <p><strong>Contact:</strong> <?= clean($trainee['contact_number']) ?: '—' ?></p>
          <p><strong>Address:</strong> <?= clean($trainee['address']) ?: '—' ?></p>
          <p><strong>Birthdate:</strong> <?= format_date($trainee['birthdate']) ?></p>
          <p class="mb-0"><strong>Gender:</strong> <?= clean($trainee['gender']) ?: '—' ?></p>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-white fw-semibold">Enrollments</div>
          <div class="table-responsive">
            <table class="table mb-0"><thead class="table-light"><tr><th>Program</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($enrollments as $e): ?>
                <tr><td><?= clean($e['title']) ?></td><td><span class="badge bg-<?= ['pending'=>'warning','approved'=>'success','rejected'=>'danger','completed'=>'secondary'][$e['status']] ?>"><?= ucfirst($e['status']) ?></span></td><td><?= date('M j, Y', strtotime($e['enrolled_at'])) ?></td></tr>
              <?php endforeach; ?>
              <?php if (!$enrollments): ?><tr><td colspan="3" class="text-center text-muted py-3">No enrollments.</td></tr><?php endif; ?>
            </tbody></table>
          </div>
        </div>
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold">Recent Activity</div>
          <div class="table-responsive">
            <table class="table mb-0"><thead class="table-light"><tr><th>Program</th><th>Activity</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($activities as $a): ?>
                <tr><td><?= clean($a['program_title']) ?></td><td><?= clean($a['activity_type']) ?> &mdash; <?= clean($a['description']) ?></td><td><?= date('M j, Y g:i A', strtotime($a['activity_date'])) ?></td></tr>
              <?php endforeach; ?>
              <?php if (!$activities): ?><tr><td colspan="3" class="text-center text-muted py-3">No activity recorded.</td></tr><?php endif; ?>
            </tbody></table>
          </div>
        </div>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT t.*, u.email, u.status AS acct_status,
        (SELECT COUNT(*) FROM enrollments e WHERE e.trainee_id=t.id AND e.status='approved') AS active_programs
        FROM trainees t JOIN users u ON u.id=t.user_id";
$params = [];
if ($search !== '') {
    $sql .= " WHERE t.full_name LIKE ? OR u.email LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY (u.status = 'pending') DESC, t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trainees = $stmt->fetchAll();

$page_title = 'Trainee Records';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-users"></i> Trainee Records</h3>
<form class="row g-2 mb-3" method="GET">
  <div class="col-md-4">
    <input type="text" name="q" class="form-control" placeholder="Search by name or email" value="<?= clean($search) ?>">
  </div>
  <div class="col-auto"><button class="btn btn-success"><i class="fa-solid fa-search"></i> Search</button></div>
</form>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light"><tr><th></th><th>Full Name</th><th>Email</th><th>Active Programs</th><th>Account</th><th>Joined</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($trainees as $t): ?>
        <tr>
          <td>
            <?php if (!empty($t['photo'])): ?>
              <img src="<?= BASE_URL ?>/assets/uploads/trainees/<?= clean($t['photo']) ?>"
                   alt="" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
            <?php else: ?>
              <i class="fa-solid fa-circle-user text-success fa-lg"></i>
            <?php endif; ?>
          </td>
          <td><?= clean($t['full_name']) ?></td>
          <td><?= clean($t['email']) ?></td>
          <td><?= (int)$t['active_programs'] ?></td>
          <td><span class="badge bg-<?= ['pending'=>'warning','active'=>'success','inactive'=>'secondary'][$t['acct_status']] ?>"><?= ucfirst($t['acct_status']) ?></span></td>
          <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
          <td>
            <a href="?view=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-eye"></i> View</a>
            <?php if ($t['acct_status'] === 'pending'): ?>
              <a href="?status_action=approve&user_id=<?= $t['user_id'] ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></a>
              <a href="?status_action=reject&user_id=<?= $t['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this registration?')"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$trainees): ?><tr><td colspan="7" class="text-center text-muted py-3">No trainees found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
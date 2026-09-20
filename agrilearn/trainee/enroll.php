<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = (int)$_POST['program_id'];
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE trainee_id=? AND program_id=?");
    $stmt->execute([$trainee['id'], $program_id]);
    if ($stmt->fetch()) {
        set_flash('warning', 'You already applied to this program.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO enrollments (trainee_id, program_id, status) VALUES (?,?,'pending')");
        $stmt->execute([$trainee['id'], $program_id]);
        set_flash('success', 'Application submitted! Please wait for admin approval.');
    }
    redirect('/trainee/enroll.php');
}

$programs = $pdo->prepare("
    SELECT p.*, (SELECT COUNT(*) FROM enrollments e WHERE e.program_id=p.id AND e.status='approved') AS enrolled_count
    FROM training_programs p WHERE p.status != 'closed'
    AND p.id NOT IN (SELECT program_id FROM enrollments WHERE trainee_id=?)
    ORDER BY p.start_date ASC
");
$programs->execute([$trainee['id']]);
$programs = $programs->fetchAll();

$page_title = 'Enroll';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-pen-to-square"></i> Available Training Programs</h3>
<div class="row g-3">
  <?php foreach ($programs as $p): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <span class="badge bg-<?= $p['status']==='open'?'success':'warning' ?> mb-2 align-self-start"><?= ucfirst($p['status']) ?></span>
        <h5><?= clean($p['title']) ?></h5>
        <p class="text-muted small mb-1"><?= clean($p['nc_level']) ?></p>
        <p class="small"><?= clean($p['description']) ?></p>
        <p class="small mb-1"><i class="fa-regular fa-calendar"></i> <?= format_date($p['start_date']) ?> &ndash; <?= format_date($p['end_date']) ?></p>
        <p class="small text-muted mb-3"><i class="fa-solid fa-users"></i> <?= (int)$p['enrolled_count'] ?> / <?= (int)$p['slots'] ?> slots filled</p>
        <form method="POST" class="mt-auto">
          <input type="hidden" name="program_id" value="<?= $p['id'] ?>">
          <button class="btn btn-success w-100" <?= $p['enrolled_count'] >= $p['slots'] ? 'disabled' : '' ?>>
            <?= $p['enrolled_count'] >= $p['slots'] ? 'Fully Booked' : 'Apply / Enroll' ?>
          </button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$programs): ?><div class="col-12"><p class="text-muted text-center py-4">No open programs available right now, or you've already applied to all of them.</p></div><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

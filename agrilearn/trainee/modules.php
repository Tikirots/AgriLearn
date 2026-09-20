<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

// Approved programs for this trainee
$stmt = $pdo->prepare("SELECT p.* FROM enrollments e JOIN training_programs p ON p.id=e.program_id WHERE e.trainee_id=? AND e.status IN ('approved','completed') ORDER BY p.start_date DESC");
$stmt->execute([$trainee['id']]);
$my_programs = $stmt->fetchAll();

$program_id = (int)($_GET['program_id'] ?? ($my_programs[0]['id'] ?? 0));

// Guard: must be one of trainee's approved programs
$valid_ids = array_column($my_programs, 'id');
if (!in_array($program_id, $valid_ids) && $my_programs) {
    $program_id = $my_programs[0]['id'];
}

$modules = [];
if ($program_id) {
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE program_id=? AND is_visible=1 ORDER BY order_num ASC");
    $stmt->execute([$program_id]);
    $modules = $stmt->fetchAll();
}

$page_title = 'Learning Modules';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-book-open"></i> Learning Modules</h3>

<?php if (!$my_programs): ?>
  <p class="text-muted">You don't have any approved enrollment yet. <a href="<?= BASE_URL ?>/trainee/enroll.php">Enroll in a program</a> first.</p>
<?php else: ?>
<form method="GET" class="mb-3">
  <select name="program_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
    <?php foreach ($my_programs as $p): ?>
      <option value="<?= $p['id'] ?>" <?= $p['id']==$program_id?'selected':'' ?>><?= clean($p['title']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="alert alert-info small"><i class="fa-solid fa-circle-info"></i> To protect the training materials, modules can only be viewed here and cannot be downloaded, copied, or printed.</div>

<div class="row g-3">
  <?php foreach ($modules as $m): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <h6><?= clean($m['title']) ?></h6>
        <p class="small text-muted"><?= clean($m['description']) ?></p>
        <a href="<?= BASE_URL ?>/trainee/module_view.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-success mt-auto"><i class="fa-solid fa-eye"></i> View Module</a>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$modules): ?><div class="col-12"><p class="text-muted text-center py-4">No modules are visible yet for this program. Your trainer will release them according to the training schedule.</p></div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

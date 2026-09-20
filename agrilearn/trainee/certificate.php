<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

$stmt = $pdo->prepare("SELECT c.*, p.title AS program_title, p.nc_level FROM certificates c JOIN training_programs p ON p.id=c.program_id WHERE c.trainee_id=? ORDER BY c.created_at DESC");
$stmt->execute([$trainee['id']]);
$certificates = $stmt->fetchAll();

$page_title = 'My Certificates';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-certificate"></i> My Certificates</h3>

<?php if (!$certificates): ?>
  <p class="text-muted">No certificates issued yet. A certificate becomes available once your program is marked completed by the training center.</p>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($certificates as $c):
    $verifyUrl = SITE_URL . '/certificate_verify.php?code=' . urlencode($c['certificate_code']);
?>
  <div class="col-12">
    <div class="card border-0 shadow-sm p-4 certificate-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
          <h5 class="mb-1"><?= clean($trainee['full_name']) ?></h5>
          <p class="mb-1">has successfully completed</p>
          <h6 class="text-success mb-1"><?= clean($c['program_title']) ?> (<?= clean($c['nc_level']) ?>)</h6>
          <p class="small text-muted mb-0">Certificate Code: <code><?= clean($c['certificate_code']) ?></code> &middot; Issued <?= format_date($c['issued_date']) ?></p>
        </div>
        <div class="text-center">
          <img src="<?= qr_code_url($verifyUrl, 130) ?>" alt="QR Verification">
          <p class="small text-muted mb-0">Scan to verify</p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/certificate_verify.php?code=<?= urlencode($c['certificate_code']) ?>" target="_blank" class="btn btn-success mt-3 align-self-start">
        <i class="fa-solid fa-print"></i> Open Printable Certificate
      </a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

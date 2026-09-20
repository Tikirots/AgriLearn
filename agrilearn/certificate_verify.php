<?php
// Public certificate verification page — no login required.
// This is the URL encoded inside every certificate's QR code.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$code = trim($_GET['code'] ?? '');
$cert = null;
if ($code !== '') {
    $stmt = $pdo->prepare("
        SELECT c.*, t.full_name, p.title AS program_title, p.nc_level, p.start_date, p.end_date
        FROM certificates c
        JOIN trainees t ON t.id = c.trainee_id
        JOIN training_programs p ON p.id = c.program_id
        WHERE c.certificate_code = ?
    ");
    $stmt->execute([$code]);
    $cert = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate Verification - <?= SITE_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<style>
  body { background: #f4f8f4; }
  .cert-box { max-width: 720px; margin: 60px auto; }
  @media print { .no-print { display:none !important; } }
</style>
</head>
<body>
<div class="container cert-box">
  <?php if ($cert): ?>
    <div class="card border-success shadow p-5 text-center certificate-paper">
      <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
      <p class="text-success fw-semibold mb-4">VERIFIED CERTIFICATE</p>
      <h2 class="mb-1">Certificate of Completion</h2>
      <p class="text-muted mb-4"><?= CENTER_NAME ?></p>
      <p class="mb-1">This certifies that</p>
      <h3 class="text-success mb-3"><?= clean($cert['full_name']) ?></h3>
      <p class="mb-1">has successfully completed the training program</p>
      <h4 class="mb-1"><?= clean($cert['program_title']) ?></h4>
      <p class="text-muted mb-4"><?= clean($cert['nc_level']) ?> &middot; <?= format_date($cert['start_date']) ?> to <?= format_date($cert['end_date']) ?></p>
      <p class="small text-muted mb-0">Certificate Code: <code><?= clean($cert['certificate_code']) ?></code></p>
      <p class="small text-muted">Issued on <?= format_date($cert['issued_date']) ?></p>
      <div class="d-flex justify-content-around align-items-center mt-4">
        <div>
          <p class="mb-0 border-top pt-1" style="width:200px;"><?= clean(CENTER_HEAD) ?></p>
          <small class="text-muted">Center President</small>
        </div>
        <img src="<?= qr_code_url(SITE_URL . '/certificate_verify.php?code=' . urlencode($cert['certificate_code']), 110) ?>" alt="QR Code">
      </div>
    </div>
    <div class="text-center mt-3 no-print">
      <button class="btn btn-success" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button>
    </div>
  <?php else: ?>
    <div class="card border-danger shadow p-5 text-center">
      <i class="fa-solid fa-circle-xmark fa-2x text-danger mb-3"></i>
      <h4>Certificate Not Found</h4>
      <p class="text-muted">The code provided does not match any issued certificate. Please check the QR code or certificate number and try again.</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>

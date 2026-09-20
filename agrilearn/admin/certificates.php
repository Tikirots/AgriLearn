<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainee_id = (int)$_POST['trainee_id'];
    $program_id = (int)$_POST['program_id'];

    $stmt = $pdo->prepare("SELECT id FROM certificates WHERE trainee_id=? AND program_id=?");
    $stmt->execute([$trainee_id, $program_id]);
    if ($stmt->fetch()) {
        set_flash('warning', 'A certificate for this trainee and program already exists.');
    } else {
        $code = generate_code('AGL');
        $stmt = $pdo->prepare("INSERT INTO certificates (trainee_id, program_id, certificate_code, issued_date) VALUES (?,?,?,CURDATE())");
        $stmt->execute([$trainee_id, $program_id, $code]);
        set_flash('success', "Certificate generated: {$code}");
    }
    redirect('/admin/certificates.php');
}

$completed = $pdo->query("
    SELECT e.trainee_id, e.program_id, t.full_name, p.title
    FROM enrollments e
    JOIN trainees t ON t.id=e.trainee_id
    JOIN training_programs p ON p.id=e.program_id
    WHERE e.status='completed'
    AND NOT EXISTS (SELECT 1 FROM certificates c WHERE c.trainee_id=e.trainee_id AND c.program_id=e.program_id)
    ORDER BY t.full_name
")->fetchAll();

$certificates = $pdo->query("
    SELECT c.*, t.full_name, p.title AS program_title
    FROM certificates c
    JOIN trainees t ON t.id=c.trainee_id
    JOIN training_programs p ON p.id=c.program_id
    ORDER BY c.created_at DESC
")->fetchAll();

$page_title = 'Certificates';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-certificate"></i> Certificate Generation</h3>

<div class="row">
  <div class="col-lg-4 mb-3">
    <div class="card border-0 shadow-sm p-3">
      <h6 class="fw-semibold mb-3">Issue New Certificate</h6>
      <p class="small text-muted">Only trainees marked "Completed" for a program are eligible.</p>
      <form method="POST">
        <select name="trainee_program" class="form-select mb-3" required onchange="const [t,p]=this.value.split('|');document.getElementById('tid').value=t;document.getElementById('pid').value=p;">
          <option value="">Select completed trainee</option>
          <?php foreach ($completed as $c): ?>
            <option value="<?= $c['trainee_id'] ?>|<?= $c['program_id'] ?>"><?= clean($c['full_name']) ?> &mdash; <?= clean($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" name="trainee_id" id="tid">
        <input type="hidden" name="program_id" id="pid">
        <button class="btn btn-success w-100"><i class="fa-solid fa-certificate"></i> Generate Certificate</button>
      </form>
      <?php if (!$completed): ?><p class="small text-muted mt-2 mb-0">No eligible trainees right now.</p><?php endif; ?>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light"><tr><th>Trainee</th><th>Program</th><th>Code</th><th>Issued</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($certificates as $c): ?>
            <tr>
              <td><?= clean($c['full_name']) ?></td>
              <td><?= clean($c['program_title']) ?></td>
              <td><code><?= clean($c['certificate_code']) ?></code></td>
              <td><?= format_date($c['issued_date']) ?></td>
              <td>
                <a href="<?= BASE_URL ?>/certificate_verify.php?code=<?= urlencode($c['certificate_code']) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-eye"></i> View</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$certificates): ?><tr><td colspan="5" class="text-center text-muted py-3">No certificates issued yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

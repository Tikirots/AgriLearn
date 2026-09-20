<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin/dashboard.php' : '/trainee/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $address   = trim($_POST['address'] ?? '');
    $contact   = trim($_POST['contact_number'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $gender    = $_POST['gender'] ?? null;

    if ($full_name === '' || $username === '' || $email === '' || $password === '') {
        $errors[] = 'Please fill in all required fields.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already taken.';
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // New trainee accounts start as 'pending' until an admin approves them.
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'trainee', 'pending')");
            $stmt->execute([$username, $email, $hash]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO trainees (user_id, full_name, address, contact_number, birthdate, gender) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $full_name, $address, $contact, $birthdate ?: null, $gender ?: null]);
            $trainee_id = $pdo->lastInsertId();

            $pdo->commit();

            // Let admins know a new registration needs approval.
            notify_admins_new_registration($pdo, (int)$trainee_id, $full_name);

            set_flash('success', 'Registration submitted! Your account is now pending review. You will be able to log in once the training center approves it.');
            redirect('/auth/login.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
$page_title = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:1040px;">
    <div class="row g-0">
      <div class="col-md-4 auth-brand-panel">
        <div>
          <div class="auth-logo-badge">
            <img src="<?= BASE_URL ?>/assets/img/agri-icon.png" alt="<?= CENTER_NAME ?> logo">
          </div>
          <h2>Join <?= SITE_NAME ?></h2>
          <p><?= CENTER_NAME ?></p>
          <ul class="auth-feature-list">
            <li><i class="fa-solid fa-seedling"></i> Free NC II agricultural training</li>
            <li><i class="fa-solid fa-calendar-check"></i> 2–3 month training sets</li>
            <li><i class="fa-solid fa-house-laptop"></i> Learn online, anywhere, anytime</li>
          </ul>
        </div>
        <p class="small mb-0" style="color:rgba(255,255,255,.7);">Already registered? <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--gold);">Log in instead</a></p>
      </div>
      <div class="col-md-8 auth-form-panel">
        <h4 class="mb-1" style="color:var(--earth);">Trainee Registration</h4>
        <p class="text-muted mb-4">Create your account to start enrolling in training programs.</p>
        <div class="alert alert-success small py-2"><i class="fa-solid fa-circle-info"></i> After you submit, your account will be <strong>pending admin approval</strong>. You'll be able to log in once the training center reviews it.</div>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger py-2"><?= clean($err) ?></div>
        <?php endforeach; ?>
        <form method="POST">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name *</label>
              <input type="text" name="full_name" class="form-control" value="<?= clean($_POST['full_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Username *</label>
              <input type="text" name="username" class="form-control" value="<?= clean($_POST['username'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="<?= clean($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Password *</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Confirm Password *</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?= clean($_POST['address'] ?? '') ?>">
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Contact Number</label>
              <input type="text" name="contact_number" class="form-control" value="<?= clean($_POST['contact_number'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-success btn-lg w-100">Register</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
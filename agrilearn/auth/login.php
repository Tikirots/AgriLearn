<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin/dashboard.php' : '/trainee/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT u.*, t.full_name FROM users u LEFT JOIN trainees t ON t.user_id = u.id WHERE u.username = ? OR u.email = ?");
    $stmt->execute([$username, $username]);
    $row = $stmt->fetch();

    if ($row && verify_password($password, $row['password'])) {
        if ($row['status'] === 'pending') {
            $error = 'Your registration is still pending admin approval. Please check back once the training center approves your account.';
        } elseif ($row['status'] === 'inactive') {
            $error = 'Your account is inactive or was not approved. Please contact the training center.';
        } else {
            $_SESSION['user'] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'role' => $row['role'],
                'name' => $row['full_name'] ?? $row['username'],
            ];
            redirect(in_array($row['role'], ['admin','trainer']) ? '/admin/dashboard.php' : '/trainee/dashboard.php');
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
$page_title = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="row g-0">
      <div class="col-md-5 auth-brand-panel">
        <div>
          <div class="auth-logo-badge">
            <img src="<?= BASE_URL ?>/assets/img/agri-icon.png" alt="<?= CENTER_NAME ?> logo">
          </div>
          <h2><?= SITE_NAME ?></h2>
          <p><?= CENTER_NAME ?></p>
          <ul class="auth-feature-list">
            <li><i class="fa-solid fa-clipboard-check"></i> Enroll and track your NC II training</li>
            <li><i class="fa-solid fa-book-open"></i> Access modules released by your trainer</li>
            <li><i class="fa-solid fa-certificate"></i> Get certificates with QR verification</li>
          </ul>
        </div>
        <p class="small mb-0" style="color:rgba(255,255,255,.7);">Growing skills, harvesting opportunities.</p>
      </div>
      <div class="col-md-7 auth-form-panel">
        <h4 class="mb-1" style="color:var(--earth);">Welcome back</h4>
        <p class="text-muted mb-4">Log in to continue your training.</p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= clean($error) ?></div><?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Username or Email</label>
            <input type="text" name="username" class="form-control form-control-lg" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" required>
          </div>
          <button type="submit" class="btn btn-success btn-lg w-100">Login</button>
        </form>
        <p class="text-center mt-4 mb-0 small">New trainee? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a></p>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
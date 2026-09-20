<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_trainee();
$user = current_user();
$user_id = $user['id'];

/* ============================================================
   REQUIRES a new column on the trainees table. Run this once:

   ALTER TABLE trainees ADD COLUMN photo VARCHAR(255) DEFAULT NULL;

   Photos are saved to /assets/uploads/trainees/ — make sure that
   folder exists and is writable by the web server:

   mkdir -p assets/uploads/trainees
   chmod 755 assets/uploads/trainees
   ============================================================ */
$upload_dir = __DIR__ . '/../assets/uploads/trainees/';
$upload_url = '/assets/uploads/trainees/';

// Get existing trainee profile (if any)
$stmt = $pdo->prepare("SELECT t.*, u.username, u.email FROM trainees t RIGHT JOIN users u ON u.id = t.user_id AND t.user_id = ? WHERE u.id = ?");
$stmt->execute([$user_id, $user_id]);
$trainee = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $birthdate      = trim($_POST['birthdate'] ?? '');
    $gender         = trim($_POST['gender'] ?? '');
    $photo_filename = $trainee['photo'] ?? null; // keep existing unless a new one is uploaded

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($contact_number !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $contact_number)) {
        $errors[] = 'Contact number looks invalid.';
    }
    if ($birthdate !== '' && !strtotime($birthdate)) {
        $errors[] = 'Birthdate is invalid.';
    }

    // Handle photo upload (optional)
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($_FILES['photo']['tmp_name']);

        if (!isset($allowed[$mime])) {
            $errors[] = 'Photo must be a JPG, PNG, or WEBP image.';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Photo must be under 2MB.';
        } else {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_filename = 'trainee_' . $user_id . '_' . time() . '.' . $allowed[$mime];
            $dest = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                // Remove the old photo file if one existed
                if (!empty($trainee['photo']) && file_exists($upload_dir . $trainee['photo'])) {
                    unlink($upload_dir . $trainee['photo']);
                }
                $photo_filename = $new_filename;
            } else {
                $errors[] = 'Could not save the uploaded photo. Check folder permissions.';
            }
        }
    }

    if (!$errors) {
        $check = $pdo->prepare("SELECT id FROM trainees WHERE user_id=?");
        $check->execute([$user_id]);
        $exists = $check->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE trainees SET full_name=?, contact_number=?, address=?, birthdate=?, gender=?, photo=? WHERE user_id=?");
            $stmt->execute([$full_name, $contact_number, $address, $birthdate ?: null, $gender, $photo_filename, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO trainees (user_id, full_name, contact_number, address, birthdate, gender, photo, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$user_id, $full_name, $contact_number, $address, $birthdate ?: null, $gender, $photo_filename]);
        }
        set_flash('success', 'Your profile has been saved. The admin can now view your details.');
        redirect('/trainee/profile.php');
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3"><i class="fa-solid fa-id-card"></i> My Profile</h3>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= clean($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3 text-center">
      <?php if (!empty($trainee['photo'])): ?>
        <img src="<?= BASE_URL . $upload_url . clean($trainee['photo']) ?>"
             alt="Profile photo"
             class="rounded-circle mx-auto mb-2"
             style="width:110px;height:110px;object-fit:cover;">
      <?php else: ?>
        <i class="fa-solid fa-circle-user fa-4x text-success mb-2"></i>
      <?php endif; ?>
      <h5><?= clean($trainee['full_name'] ?? $user['name']) ?></h5>
      <p class="text-muted mb-0"><?= clean($trainee['email'] ?? '') ?></p>
      <?php if (!$trainee || !$trainee['full_name']): ?>
        <span class="badge bg-warning mt-2">Profile not completed yet</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Edit Profile</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">JPG, PNG or WEBP. Max 2MB.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control"
                   value="<?= clean($trainee['full_name'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact_number" class="form-control"
                   value="<?= clean($trainee['contact_number'] ?? '') ?>" placeholder="e.g. 09171234567">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"><?= clean($trainee['address'] ?? '') ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" class="form-control"
                     value="<?= clean($trainee['birthdate'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="">-- Select --</option>
                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                  <option value="<?= $g ?>" <?= (($trainee['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fa-solid fa-floppy-disk"></i> Save Profile
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
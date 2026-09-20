<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_admin();

$user = current_user();
mark_all_notifications_read($pdo, $user['id']);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$user['id']]);
$all_notifications = $stmt->fetchAll();

$page_title = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">All Notifications</div>
  <div class="list-group list-group-flush">
    <?php foreach ($all_notifications as $n): ?>
      <a href="<?= clean($n['link'] ?: '#') ?>" class="list-group-item list-group-item-action">
        <div class="d-flex justify-content-between">
          <strong><?= clean($n['title']) ?></strong>
          <small class="text-muted"><?= time_ago($n['created_at']) ?></small>
        </div>
        <div class="text-muted small"><?= clean($n['message']) ?></div>
      </a>
    <?php endforeach; ?>
    <?php if (!$all_notifications): ?>
      <div class="list-group-item text-center text-muted py-4">No notifications yet.</div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// Expects $page_title to be set by the including page.
require_once __DIR__ . '/notifications.php';
$user = current_user();

$unread_count = 0;
$recent_notifications = [];
if ($user) {
    $unread_count = get_unread_notification_count($pdo, $user['id']);
    $recent_notifications = get_recent_notifications($pdo, $user['id'], 8);
}

function al_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials ?: '?';
}

function al_nav_active(string $file, string $current): string {
    return $file === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? clean($page_title) . ' - ' : '' ?><?= SITE_NAME ?></title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<link rel="icon" href="<?= BASE_URL ?>/assets/img/agri-icon.png">
<meta name="theme-color" content="#2F6B3A">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if ($user): ?>
<div class="app-shell">

  <aside class="app-sidebar" id="appSidebar">
    <a class="app-sidebar-brand" href="<?= BASE_URL ?>/<?= is_admin() ? 'admin' : 'trainee' ?>/dashboard.php">
      <img src="<?= BASE_URL ?>/assets/img/agri-icon.png" alt="<?= CENTER_NAME ?> logo">
      <span><?= SITE_NAME ?></span>
    </a>

    <nav class="app-sidebar-nav">
      <?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
      <?php if (is_admin()): ?>
        <a class="<?= al_nav_active('dashboard.php', $current) ?>" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fa-solid fa-house"></i> Overview</a>
        <a class="<?= al_nav_active('trainees.php', $current) ?>" href="<?= BASE_URL ?>/admin/trainees.php"><i class="fa-solid fa-user-group"></i> Trainees</a>
        <a class="<?= al_nav_active('programs.php', $current) ?>" href="<?= BASE_URL ?>/admin/programs.php"><i class="fa-solid fa-layer-group"></i> Programs</a>
        <a class="<?= al_nav_active('modules.php', $current) ?>" href="<?= BASE_URL ?>/admin/modules.php"><i class="fa-solid fa-book"></i> Modules</a>
        <a class="<?= al_nav_active('enrollments.php', $current) ?>" href="<?= BASE_URL ?>/admin/enrollments.php"><i class="fa-solid fa-user-check"></i> Enrollments</a>
        <a class="<?= al_nav_active('activities.php', $current) ?>" href="<?= BASE_URL ?>/admin/activities.php"><i class="fa-solid fa-clipboard-list"></i> Activities</a>
        <a class="<?= al_nav_active('certificates.php', $current) ?>" href="<?= BASE_URL ?>/admin/certificates.php"><i class="fa-solid fa-award"></i> Certificates</a>
      <?php else: ?>
        <a class="<?= al_nav_active('dashboard.php', $current) ?>" href="<?= BASE_URL ?>/trainee/dashboard.php"><i class="fa-solid fa-house"></i> Overview</a>
        <a class="<?= al_nav_active('modules.php', $current) ?>" href="<?= BASE_URL ?>/trainee/modules.php"><i class="fa-solid fa-book-open"></i> Learning Modules</a>
        <a class="<?= al_nav_active('my_enrollments.php', $current) ?>" href="<?= BASE_URL ?>/trainee/my_enrollments.php"><i class="fa-solid fa-calendar-days"></i> Schedule</a>
        <a class="<?= al_nav_active('certificate.php', $current) ?>" href="<?= BASE_URL ?>/trainee/certificate.php"><i class="fa-solid fa-award"></i> Certificates</a>
        <a class="<?= al_nav_active('profile.php', $current) ?>" href="<?= BASE_URL ?>/trainee/profile.php"><i class="fa-solid fa-id-card"></i> My Profile</a>
      <?php endif; ?>
    </nav>

    <div class="app-sidebar-foot">
      <div class="app-sidebar-user">
        <span class="app-avatar app-avatar-sm"><?= clean(al_initials($user['name'])) ?></span>
        <span class="app-sidebar-user-info">
          <strong><?= clean($user['name']) ?></strong>
          <small><?= clean(ucfirst($user['role'])) ?></small>
        </span>
      </div>
      <a href="<?= BASE_URL ?>/auth/logout.php" class="app-logout-btn" title="Log out"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>

  <div class="al-overlay" id="alOverlay"></div>

  <div class="app-main">
    <header class="app-topbar">
      <button class="al-burger" id="alBurgerBtn" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
      <h1 class="app-page-title"><?= isset($page_title) ? clean($page_title) : '' ?></h1>

      <div class="app-topbar-actions">
        <div class="notif-wrap">
          <button class="notif-bell" id="notifBellBtn" aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            <?php if ($unread_count > 0): ?>
              <span class="notif-badge" id="notifBadge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
            <?php endif; ?>
          </button>
          <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dropdown-head">
              <span>Notifications</span>
              <?php if ($unread_count > 0): ?><button type="button" id="notifMarkAllBtn">Mark all read</button><?php endif; ?>
            </div>
            <div class="notif-list">
              <?php if (!$recent_notifications): ?>
                <div class="notif-empty">You're all caught up.</div>
              <?php endif; ?>
              <?php foreach ($recent_notifications as $n): ?>
                <a class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= clean($n['link'] ?: '#') ?>">
                  <span class="notif-item-title"><?= clean($n['title']) ?></span>
                  <span class="notif-item-msg"><?= clean($n['message']) ?></span>
                  <span class="notif-item-time"><?= time_ago($n['created_at']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
            <div class="notif-dropdown-foot">
              <a href="<?= BASE_URL ?>/<?= is_admin() ? 'admin' : 'trainee' ?>/notifications.php">View all</a>
            </div>
          </div>
        </div>
        <span class="app-avatar" title="<?= clean($user['name']) ?>"><?= clean(al_initials($user['name'])) ?></span>
      </div>
    </header>

    <div class="app-content">
<?php else: ?>
<div class="app-shell app-shell-guest">
  <div class="app-main app-main-full">
    <div class="app-content">
<?php endif; ?>

<?php $flash = get_flash(); if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
    <?= clean($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
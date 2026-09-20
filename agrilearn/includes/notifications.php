<?php
/**
 * Notification helpers.
 * Assumes $pdo (config/db.php) and BASE_URL are already available
 * wherever this file is included.
 */

function create_notification(PDO $pdo, int $user_id, string $type, string $title, ?string $message = null, ?string $link = null): void
{
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $type, $title, $message, $link]);
}

/**
 * Notify every approved trainee enrolled in a program that a module was
 * released, and log a confirmation notification for the admin who released it.
 */
function notify_module_published(PDO $pdo, int $program_id, string $module_title, int $admin_user_id): void
{
    $stmt = $pdo->prepare("SELECT title FROM training_programs WHERE id = ?");
    $stmt->execute([$program_id]);
    $program_title = $stmt->fetchColumn() ?: 'your program';

    $stmt = $pdo->prepare("
        SELECT u.id AS user_id
        FROM enrollments e
        JOIN trainees t ON t.id = e.trainee_id
        JOIN users u ON u.id = t.user_id
        WHERE e.program_id = ? AND e.status = 'approved'
    ");
    $stmt->execute([$program_id]);
    $trainee_user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $link = BASE_URL . '/trainee/modules.php?program_id=' . $program_id;

    foreach ($trainee_user_ids as $uid) {
        create_notification(
            $pdo,
            (int)$uid,
            'module_released',
            'New module released',
            "\"$module_title\" is now available in $program_title.",
            $link
        );
    }

    create_notification(
        $pdo,
        $admin_user_id,
        'module_released_confirm',
        'Module published',
        "\"$module_title\" was released to " . count($trainee_user_ids) . " trainee(s) in $program_title.",
        BASE_URL . '/admin/modules.php?program_id=' . $program_id
    );
}

/**
 * Notify every admin/trainer user that a new trainee registration is
 * waiting for approval.
 */
function notify_admins_new_registration(PDO $pdo, int $trainee_id, string $trainee_name): void
{
    $admin_ids = $pdo->query("SELECT id FROM users WHERE role IN ('admin','trainer')")->fetchAll(PDO::FETCH_COLUMN);

    $link = BASE_URL . '/admin/trainees.php?view=' . $trainee_id;
    foreach ($admin_ids as $admin_id) {
        create_notification(
            $pdo,
            (int)$admin_id,
            'trainee_registered',
            'New registration pending approval',
            "$trainee_name just registered and is waiting for approval.",
            $link
        );
    }
}

/**
 * Notify a trainee that their account was approved or rejected.
 */
function notify_trainee_approval_status(PDO $pdo, int $user_id, bool $approved): void
{
    create_notification(
        $pdo,
        $user_id,
        $approved ? 'account_approved' : 'account_rejected',
        $approved ? 'Your account was approved' : 'Registration not approved',
        $approved
            ? 'You can now log in and enroll in a training program.'
            : 'Your registration was not approved. Please contact the training center for details.',
        $approved ? BASE_URL . '/auth/login.php' : null
    );
}

function get_unread_notification_count(PDO $pdo, int $user_id): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function get_recent_notifications(PDO $pdo, int $user_id, int $limit = 8): array
{
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function mark_all_notifications_read(PDO $pdo, int $user_id): void
{
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', strtotime($datetime));
}

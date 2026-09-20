<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin/dashboard.php' : '/trainee/dashboard.php');
} else {
    redirect('/auth/login.php');
}

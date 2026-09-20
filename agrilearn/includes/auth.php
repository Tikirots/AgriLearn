<?php
// =========================================================
// AgriLearn - Authentication Guards
// =========================================================
require_once __DIR__ . '/functions.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_admin() {
    return is_logged_in() && in_array($_SESSION['user']['role'], ['admin', 'trainer']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        redirect('/trainee/dashboard.php');
    }
}

function require_trainee() {
    require_login();
    if ($_SESSION['user']['role'] !== 'trainee') {
        redirect('/admin/dashboard.php');
    }
}

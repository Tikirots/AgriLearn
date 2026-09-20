<?php
// =========================================================
// AgriLearn - Helper Functions
// =========================================================

function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generate_code($prefix = 'AGL') {
    return strtoupper($prefix . '-' . bin2hex(random_bytes(4)) . '-' . date('Y'));
}

function qr_code_url($data, $size = 200) {
    // Uses the free QR Server API to render a QR image at request time.
    // No local QR library/dependency needed. Requires the deployed server
    // to have outbound internet access.
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
}
function verify_password($input, $stored) {
    if (preg_match('/^\$2[ayb]\$/', $stored)) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input);
}

function format_date($date) {
    if (!$date) return '—';
    return date('F j, Y', strtotime($date));
}

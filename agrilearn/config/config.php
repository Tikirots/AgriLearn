<?php
// =========================================================
// AgriLearn - Site Configuration
// =========================================================

// Adjust this if the project lives in a subfolder, e.g. '/agrilearn'
define('BASE_URL', '/agrilearn');

define('UPLOAD_MODULES_DIR', __DIR__ . '/../uploads/modules/');
define('UPLOAD_PHOTOS_DIR', __DIR__ . '/../uploads/photos/');
define('UPLOAD_CERTS_DIR', __DIR__ . '/../uploads/certificates/');

define('SITE_NAME', 'AgriLearn');
define('CENTER_NAME', 'Masaganang Bukid Agricultural Learning Center');
define('CENTER_HEAD', 'Mr. Francis B. Cuento');

// Public verification base URL used to build the QR code content.
// Change to your live domain when deployed, e.g. https://agrilearn.example.com
define('SITE_URL', 'http://localhost' . BASE_URL);

date_default_timezone_set('Asia/Manila');
session_start();

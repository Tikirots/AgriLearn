<?php
// Streams a module file inline (no forced download) after verifying
// the logged-in trainee is approved for the program and the module
// is currently marked visible.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_trainee();

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id=?");
$stmt->execute([$user['id']]);
$trainee = $stmt->fetch();

$module_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id=? AND is_visible=1");
$stmt->execute([$module_id]);
$module = $stmt->fetch();
if (!$module) { http_response_code(404); exit('Not found'); }

$stmt = $pdo->prepare("SELECT id FROM enrollments WHERE trainee_id=? AND program_id=? AND status IN ('approved','completed')");
$stmt->execute([$trainee['id'], $module['program_id']]);
if (!$stmt->fetch()) { http_response_code(403); exit('Forbidden'); }

$path = UPLOAD_MODULES_DIR . $module['file_path'];
if (!file_exists($path)) { http_response_code(404); exit('File missing'); }

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = ['pdf'=>'application/pdf','mp4'=>'video/mp4','ppt'=>'application/vnd.ms-powerpoint',
    'pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($path);

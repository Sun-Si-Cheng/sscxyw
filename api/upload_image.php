<?php
/**
 * 富文本编辑器图片上传接口
 * POST multipart/form-data, 字段名: file 或 image
 * 返回 JSON: { "url": "uploads/images/...", "id": 123 } 或 { "error": "..." }
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$file = $_FILES['file'] ?? $_FILES['image'] ?? null;
if (!$file || ($file['error'] !== UPLOAD_ERR_OK)) {
    $msg = '未选择文件或上传失败';
    if ($file && $file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件过大',
            UPLOAD_ERR_PARTIAL => '文件只上传了部分',
            UPLOAD_ERR_NO_FILE => '未选择文件',
        ];
        $msg = $errors[$file['error']] ?? '上传失败';
    }
    echo json_encode(['error' => $msg]);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['error' => '只允许上传 JPG、PNG、GIF、WebP 图片']);
    exit;
}

$maxSize = defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : (2 * 1024 * 1024);
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => '图片大小不能超过 ' . round($maxSize / 1024 / 1024) . 'MB']);
    exit;
}

$baseDir = dirname(__DIR__) . '/uploads/images';
$subDir = date('Y') . '/' . date('m');
$dir = $baseDir . '/' . $subDir;
if (!is_dir($dir)) {
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0755, true);
    }
    @mkdir($dir, 0755, true);
}
if (!is_writable($dir)) {
    echo json_encode(['error' => '上传目录不可写']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
$safeExt = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
$filename = bin2hex(random_bytes(8)) . '.' . $safeExt;
$path = $subDir . '/' . $filename;
$fullPath = $baseDir . '/' . $path;

if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
    echo json_encode(['error' => '保存文件失败']);
    exit;
}

$pdo = getDBConnection();
$postId = isset($_POST['post_id']) ? max(0, (int) $_POST['post_id']) : 0;
$stmt = $pdo->prepare("INSERT INTO post_images (post_id, path, original_name, size, mime_type) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$postId, $path, $file['name'], $file['size'], $mime]);
$id = (int) $pdo->lastInsertId();

$url = 'uploads/images/' . $path;
echo json_encode(['url' => $url, 'id' => $id]);

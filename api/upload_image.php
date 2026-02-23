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

// 检查是否有文件上传
$file = $_FILES['file'] ?? $_FILES['image'] ?? null;
if (!$file || ($file['error'] !== UPLOAD_ERR_OK)) {
    $msg = '未选择文件或上传失败';
    if ($file && $file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件过大',
            UPLOAD_ERR_PARTIAL => '文件只上传了部分',
            UPLOAD_ERR_NO_FILE => '未选择文件',
            UPLOAD_ERR_NO_TMP_DIR => '临时目录不存在',
            UPLOAD_ERR_CANT_WRITE => '写入文件失败',
            UPLOAD_ERR_EXTENSION => '文件扩展名不允许',
        ];
        $msg = $errors[$file['error']] ?? '上传失败';
    }
    echo json_encode(['error' => $msg]);
    exit;
}

// 验证文件类型（双重检查）
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// 1. MIME类型检查
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['error' => '只允许上传 JPG、PNG、GIF、WebP 图片']);
    exit;
}

// 2. 文件扩展名检查
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts)) {
    echo json_encode(['error' => '只允许上传 JPG、PNG、GIF、WebP 图片']);
    exit;
}

// 验证文件大小
$maxSize = defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : (2 * 1024 * 1024);
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => '图片大小不能超过 ' . round($maxSize / 1024 / 1024) . 'MB']);
    exit;
}

// 验证文件是否为真实图片
if (!getimagesize($file['tmp_name'])) {
    echo json_encode(['error' => '请上传有效的图片文件']);
    exit;
}

// 准备上传目录
$baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images';
$subDir = date('Y') . DIRECTORY_SEPARATOR . date('m');
$dir = $baseDir . DIRECTORY_SEPARATOR . $subDir;

// 创建目录（安全的方式）
if (!is_dir($dir)) {
    if (!is_dir($baseDir)) {
        if (!mkdir($baseDir, 0755, true)) {
            echo json_encode(['error' => '创建上传目录失败']);
            exit;
        }
    }
    if (!mkdir($dir, 0755, true)) {
        echo json_encode(['error' => '创建上传目录失败']);
        exit;
    }
}

// 检查目录是否可写
if (!is_writable($dir)) {
    echo json_encode(['error' => '上传目录不可写']);
    exit;
}

// 生成安全的文件名
$safeExt = in_array($ext, $allowedExts) ? $ext : 'jpg';
$filename = bin2hex(random_bytes(16)) . '.' . $safeExt; // 增加随机字符长度
$path = $subDir . DIRECTORY_SEPARATOR . $filename;
$fullPath = $baseDir . DIRECTORY_SEPARATOR . $path;

// 确保文件路径安全
if (strpos(realpath($fullPath), realpath($baseDir)) !== 0) {
    echo json_encode(['error' => '文件路径无效']);
    exit;
}

// 保存文件
if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
    echo json_encode(['error' => '保存文件失败']);
    exit;
}

// 调整文件权限
chmod($fullPath, 0644);

// 保存到数据库
$pdo = getDBConnection();
$postId = isset($_POST['post_id']) ? max(0, (int) $_POST['post_id']) : 0;
$id = 0;

try {
    $stmt = $pdo->prepare("INSERT INTO post_images (post_id, path, original_name, size, mime_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$postId, $path, $file['name'], $file['size'], $mime]);
    $id = (int) $pdo->lastInsertId();
} catch (Exception $e) {
    // 记录错误但不影响上传
    error_log('保存图片记录失败: ' . $e->getMessage());
}

$url = 'uploads/images/' . $path;
echo json_encode(['url' => $url, 'id' => $id]);

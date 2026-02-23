<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    $u = getCurrentUser();
    if ($u && $u['role'] === 'admin') {
        redirect('index.php');
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = '无效请求，请刷新页面重试';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码';
        } else {
            $pdo = getDBConnection();
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] != 1) {
                        $error = '账号已被禁用，请联系管理员';
                    } elseif ($user['role'] !== 'admin') {
                        $error = '无后台管理权限';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        
                        // 记录管理员登录
                        admin_log($user['id'], '登录后台');
                        
                        redirect('index.php');
                    }
                } else {
                    $error = '用户名或密码错误';
                }
            } catch (PDOException $e) {
                if (ENVIRONMENT === 'development') {
                    $error = '登录失败: ' . $e->getMessage();
                } else {
                    $error = '登录失败，请稍后重试';
                    error_log('管理员登录失败: ' . $e->getMessage());
                }
            }
        }
    }
}

$pageTitle = '后台登录';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($pageTitle); ?> - <?php echo clean(SITE_NAME); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="post-form-container" style="max-width: 400px;">
        <div class="post-form-box">
            <h2><i class="fas fa-lock"></i> 后台登录</h2>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo clean($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo clean(generateCSRFToken()); ?>">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? clean($_POST['username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-actions">
                    <a href="../index.php" class="btn btn-outline">返回首页</a>
                    <button type="submit" class="btn btn-primary">登录</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

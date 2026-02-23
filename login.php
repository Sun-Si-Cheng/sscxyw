<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
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
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 0) {
                    $error = '账号已被禁用，请联系管理员';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // 处理"记住我"功能
                    if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                        // 生成安全令牌
                        $token = bin2hex(random_bytes(32));
                        $expiresAt = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30天有效期
                        
                        // 存储令牌到数据库
                        $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
                        $stmt->execute([$user['id'], $token, $expiresAt, $token, $expiresAt]);
                        
                        // 设置cookie
                        setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', isset($_SERVER['HTTPS']), true);
                    }
                    
                    redirect('index.php');
                }
            } else {
                $error = '用户名或密码错误';
            }
        }
    }
}

$pageTitle = '用户登录';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2><i class="fas fa-sign-in-alt"></i> 用户登录</h2>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-group">
                <label for="username">用户名/邮箱</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required 
                           placeholder="请输入用户名或邮箱" value="<?php echo isset($_POST['username']) ? clean($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required 
                           placeholder="请输入密码">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group form-options">
                <label class="checkbox">
                    <input type="checkbox" name="remember"> 记住我
                </label>
                <a href="forgot-password.php" class="forgot-link">忘记密码？</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> 登录
            </button>
        </form>
        
        <div class="auth-footer">
            <p>还没有账号？<a href="register.php">立即注册</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

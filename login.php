<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                
                // 更新最后登录时间
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                redirect('index.php');
            }
        } else {
            $error = '用户名或密码错误';
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

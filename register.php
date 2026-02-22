<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $nickname = trim($_POST['nickname'] ?? '');
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password)) {
        $error = '请填写所有必填项';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度应在3-20个字符之间';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = '用户名只能包含字母、数字和下划线';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少为6个字符';
    } elseif ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } else {
        $pdo = getDBConnection();
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = '用户名已存在';
        } else {
            // 检查邮箱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = '邮箱已被注册';
            } else {
                // 创建新用户
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $displayName = empty($nickname) ? $username : $nickname;
                
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, nickname) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $hashedPassword, $email, $displayName])) {
                    $success = '注册成功！正在跳转到登录页面...';
                    header("Refresh: 2; URL=login.php");
                } else {
                    $error = '注册失败，请稍后重试';
                }
            }
        }
    }
}

$pageTitle = '用户注册';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2><i class="fas fa-user-plus"></i> 用户注册</h2>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="username">用户名 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required 
                           placeholder="3-20个字符，支持字母、数字、下划线"
                           value="<?php echo isset($_POST['username']) ? clean($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">邮箱 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required 
                           placeholder="请输入有效的邮箱地址"
                           value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="nickname">昵称</label>
                <div class="input-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="nickname" name="nickname" 
                           placeholder="选填，不填则使用用户名"
                           value="<?php echo isset($_POST['nickname']) ? clean($_POST['nickname']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">密码 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required 
                           placeholder="至少6个字符">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">确认密码 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="再次输入密码">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group form-options">
                <label class="checkbox">
                    <input type="checkbox" name="agree" required> 
                    我已阅读并同意<a href="terms.php" target="_blank">用户协议</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> 注册
            </button>
        </form>
        
        <div class="auth-footer">
            <p>已有账号？<a href="login.php">立即登录</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = '无效请求，请刷新页面重试';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // 验证输入
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = '请填写所有密码字段';
        } elseif (!password_verify($currentPassword, $currentUser['password'])) {
            $error = '当前密码不正确';
        } elseif (strlen($newPassword) < 8) {
            $error = '新密码长度至少为8个字符';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $error = '新密码必须包含字母和数字';
        } elseif ($newPassword !== $confirmPassword) {
            $error = '两次输入的新密码不一致';
        } else {
            // 更新密码
            $pdo = getDBConnection();
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashedPassword, $currentUser['id']])) {
                    // 清除记住我令牌，强制重新登录
                    $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
                    $stmt->execute([$currentUser['id']]);
                    
                    // 清除cookie
                    if (isset($_COOKIE['remember_token'])) {
                        setcookie('remember_token', '', time() - 42000, '/', '', isset($_SERVER['HTTPS']), true);
                    }
                    
                    $success = '密码修改成功！请重新登录。';
                    // 3秒后跳转到登录页面
                    echo '<script>setTimeout(function() { window.location.href = "login.php"; }, 3000);</script>';
                } else {
                    $error = '密码修改失败，请稍后重试';
                }
            } catch (PDOException $e) {
                if (ENVIRONMENT === 'development') {
                    $error = '密码修改失败: ' . $e->getMessage();
                } else {
                    $error = '密码修改失败，请稍后重试';
                    error_log('修改密码失败: ' . $e->getMessage());
                }
            }
        }
    }
}

$pageTitle = '修改密码';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2><i class="fas fa-lock"></i> 修改密码</h2>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-group">
                <label for="current_password">当前密码 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="current_password" name="current_password" required 
                           placeholder="请输入当前密码">
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">新密码 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="至少6个字符">
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">确认新密码 <span class="required">*</span></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="再次输入新密码">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="profile.php" class="btn btn-outline">返回</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 修改密码
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

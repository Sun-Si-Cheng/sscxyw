<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pageTitle = '找回密码';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2><i class="fas fa-key"></i> 找回密码</h2>
        <div class="alert alert-info">
            当前版本暂未开启在线找回密码功能，如需重置密码，请联系站点管理员处理。
        </div>
        <div class="auth-footer">
            <p>记起密码了？<a href="login.php">返回登录</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


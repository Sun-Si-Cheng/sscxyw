<?php
require_once __DIR__ . '/includes/functions.php';

// 清除记住我令牌
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmt->execute([$token]);
    } catch (Exception $e) {
        // 忽略错误
    }
    
    // 清除cookie
    setcookie('remember_token', '', time() - 42000, '/', '', isset($_SERVER['HTTPS']), true);
}

// 清除会话
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

redirect('index.php');

<?php
/**
 * 后台认证：必须登录且 role = admin
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$adminUser = getCurrentUser();
if (!$adminUser || $adminUser['status'] != 1) {
    session_destroy();
    header('Location: login.php');
    exit;
}
if ($adminUser['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

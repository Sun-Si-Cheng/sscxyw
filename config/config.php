<?php
// 全局配置文件

// 环境设置
define('ENVIRONMENT', 'development'); // 可选值: development, production

// 错误报告设置
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/logs/error.log');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// 网站基础配置
define('SITE_NAME', '校园论坛');
define('SITE_URL', 'http://localhost/campus-forum');
define('SITE_DESCRIPTION', '一个属于校园师生的交流平台');

// 分页配置
define('POSTS_PER_PAGE', 10);
define('COMMENTS_PER_PAGE', 20);

// 上传配置
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 引入数据库配置
require_once __DIR__ . '/database.php';

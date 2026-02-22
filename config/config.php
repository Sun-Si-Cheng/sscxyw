<?php
// 全局配置文件

session_start();

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

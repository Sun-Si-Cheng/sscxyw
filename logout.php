<?php
require_once __DIR__ . '/includes/functions.php';

// 清除所有会话数据
$_SESSION = [];

// 销毁会话
session_destroy();

// 重定向到首页
redirect('index.php');

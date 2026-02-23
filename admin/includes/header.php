<?php
$adminUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>后台管理</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div style="padding: 0 1.25rem 1rem; font-weight: 600;">后台管理</div>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> 仪表盘</a>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> 用户管理</a>
            <a href="posts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> 帖子管理</a>
            <a href="notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> 通知管理</a>
            <a href="../index.php" style="margin-top: 1rem;"><i class="fas fa-external-link-alt"></i> 返回站点</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出</a>
        </aside>
        <main class="admin-main">

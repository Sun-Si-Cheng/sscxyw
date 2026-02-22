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
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 220px; background: #1f2937; color: #fff; padding: 1rem 0; }
        .admin-sidebar a { display: block; padding: 0.5rem 1.25rem; color: #d1d5db; text-decoration: none; }
        .admin-sidebar a:hover, .admin-sidebar a.active { background: #374151; color: #fff; }
        .admin-main { flex: 1; padding: 1.5rem; background: #f3f4f6; }
        .admin-page-title { font-size: 1.25rem; margin-bottom: 1rem; }
        .admin-card { background: #fff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.25rem; margin-bottom: 1rem; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .admin-table th { background: #f9fafb; font-weight: 600; }
    </style>
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

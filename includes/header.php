<?php
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? clean($pageTitle) . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="index.php" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                
                <form method="GET" action="search.php" class="header-search-form">
                    <input type="text" name="q" placeholder="搜索..." value="<?php echo isset($_GET['q']) ? clean($_GET['q']) : ''; ?>">
                    <button type="submit" class="btn btn-sm"><i class="fas fa-search"></i></button>
                </form>
                <nav class="main-nav">
                    <a href="index.php" class="<?php echo !isset($_GET['category']) ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> 首页
                    </a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="category.php?id=<?php echo $cat['id']; ?>" 
                       class="<?php echo isset($_GET['id']) && $_GET['id'] == $cat['id'] ? 'active' : ''; ?>">
                        <i class="fas fa-<?php echo $cat['icon']; ?>"></i> <?php echo clean($cat['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
                
                <div class="header-actions">
                    <?php if ($currentUser): ?>
                        <a href="post-create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 发布帖子
                        </a>
                        <?php $notifCount = function_exists('getUnreadNotificationCount') ? getUnreadNotificationCount($currentUser['id']) : 0; ?>
                        <div class="notification-dropdown">
                            <a href="notifications.php" class="notification-trigger" id="notificationTrigger" title="通知">
                                <i class="fas fa-bell"></i>
                                <?php if ($notifCount > 0): ?><span class="notification-badge"><?php echo $notifCount > 99 ? '99+' : $notifCount; ?></span><?php endif; ?>
                            </a>
                            <div class="notification-dropdown-menu" id="notificationDropdown">
                                <div class="notification-dropdown-header">
                                    <a href="notifications.php">通知中心</a>
                                </div>
                                <div class="notification-dropdown-list" id="notificationDropdownList">
                                    <p class="text-muted">加载中...</p>
                                </div>
                            </div>
                        </div>
                        <div class="user-menu">
                            <a href="profile.php" class="user-avatar">
                                <img src="<?php echo getAvatarUrl($currentUser['avatar']); ?>" alt="<?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?>">
                                <span><?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu">
                                <a href="profile.php"><i class="fas fa-user"></i> 个人中心</a>
                                <a href="my-posts.php"><i class="fas fa-file-alt"></i> 我的帖子</a>
                                <a href="messages.php"><i class="fas fa-envelope"></i> 站内消息</a>
                                <?php if (isAdmin()): ?>
                                <a href="admin/"><i class="fas fa-cog"></i> 后台管理</a>
                                <?php endif; ?>
                                <div class="divider"></div>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">登录</a>
                        <a href="register.php" class="btn btn-primary">注册</a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    
    <div class="mobile-menu" id="mobileMenu">
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> 首页</a>
            <?php foreach ($categories as $cat): ?>
            <a href="category.php?id=<?php echo $cat['id']; ?>">
                <i class="fas fa-<?php echo $cat['icon']; ?>"></i> <?php echo clean($cat['name']); ?>
            </a>
            <?php endforeach; ?>
            <?php if ($currentUser): ?>
            <a href="post-create.php"><i class="fas fa-plus"></i> 发布帖子</a>
            <a href="profile.php"><i class="fas fa-user"></i> 个人中心</a>
            <a href="messages.php"><i class="fas fa-envelope"></i> 站内消息</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> 登录</a>
            <a href="register.php"><i class="fas fa-user-plus"></i> 注册</a>
            <?php endif; ?>
        </nav>
    </div>
    
    <main class="main-content">

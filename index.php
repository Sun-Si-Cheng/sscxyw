<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

$search = sanitize($_GET['search'] ?? '');
$where = '';
if (!empty($search)) {
    $where = "WHERE p.title LIKE '%$search%' OR p.content LIKE '%$search%'";
}

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total_result = $conn->query("SELECT COUNT(*) as count FROM posts p $where");
$total_posts = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_posts / $per_page);

$total_users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$total_users = $total_users_result->fetch_assoc()['count'];

$total_replies_result = $conn->query("SELECT COUNT(*) as count FROM replies");
$total_replies = $total_replies_result->fetch_assoc()['count'];

$sql = "SELECT p.*, u.username, 
        (SELECT COUNT(*) FROM replies WHERE post_id = p.id) as reply_count 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        $where 
        ORDER BY p.created_at DESC 
        LIMIT $offset, $per_page";
$posts = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>校园论坛</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">
                    <a href="index.php">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        校园论坛
                    </a>
                </h1>
                <div class="search-box">
                    <form method="GET" action="">
                        <input type="text" name="search" placeholder="搜索帖子..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-white">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            搜索
                        </button>
                    </form>
                </div>
                <div class="user-info">
                    <span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-outline-white">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        退出
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="hero-section">
                <h1>欢迎来到校园论坛</h1>
                <p>分享知识，交流思想，共同成长</p>
                <a href="create_post.php" class="btn btn-cta">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    发布新帖
                </a>
            </div>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $total_posts; ?></div>
                    <div class="stat-label">帖子总数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">注册用户</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $total_replies; ?></div>
                    <div class="stat-label">回复总数</div>
                </div>
            </div>

            <div class="page-header">
                <h2>
                    <?php if (!empty($search)): ?>
                        搜索结果: "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        最新帖子
                    <?php endif; ?>
                </h2>
                <a href="create_post.php" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    发布新帖
                </a>
            </div>

            <?php if ($posts->num_rows > 0): ?>
                <div class="post-list">
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <div class="post-item" onclick="window.location.href='view_post.php?id=<?php echo $post['id']; ?>'">
                            <div class="post-info">
                                <h3 class="post-title">
                                    <a href="view_post.php?id=<?php echo $post['id']; ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <div class="post-meta">
                                    <span class="author">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars($post['username']); ?>
                                    </span>
                                    <span class="time">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?>
                                    </span>
                                    <span class="views">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <?php echo $post['views']; ?>
                                    </span>
                                    <span class="replies">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                        <?php echo $post['reply_count']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                上一页
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-outline">
                                下一页
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 16px;">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        <line x1="9" y1="10" x2="15" y2="10"></line>
                    </svg>
                    <p>暂无帖子，快来发布第一篇吧！</p>
                    <a href="create_post.php" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        发布新帖
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> 校园论坛 - 交流学习，共同进步</p>
        </div>
    </footer>
</body>
</html>

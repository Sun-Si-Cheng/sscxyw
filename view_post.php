<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$post_id = intval($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header('Location: index.php');
    exit;
}

$conn->query("UPDATE posts SET views = views + 1 WHERE id = $post_id");

$sql = "SELECT p.*, u.username 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = $post_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$post = $result->fetch_assoc();

$reply_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = sanitize($_POST['content'] ?? '');
    
    if (empty($content)) {
        $reply_error = '请输入回复内容';
    } else {
        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO replies (post_id, user_id, content) VALUES ($post_id, $user_id, '$content')";
        if ($conn->query($sql)) {
            header("Location: view_post.php?id=$post_id");
            exit;
        } else {
            $reply_error = '回复失败，请稍后重试';
        }
    }
}

$replies_sql = "SELECT r.*, u.username 
                FROM replies r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.post_id = $post_id 
                ORDER BY r.created_at ASC";
$replies = $conn->query($replies_sql);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - 校园论坛</title>
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
            <div class="back-link" style="margin-bottom: 24px;">
                <a href="index.php" class="btn btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    返回列表
                </a>
            </div>

            <div class="post-detail">
                <div class="post-header">
                    <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                    <div class="post-meta">
                        <span class="author">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php echo htmlspecialchars($post['username']); ?>
                        </span>
                        <span class="time">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?>
                        </span>
                        <span class="views">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <?php echo $post['views']; ?> 次浏览
                        </span>
                    </div>
                </div>
                
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
            </div>

            <div class="replies-section">
                <h3>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    回复 (<?php echo $replies->num_rows; ?>)
                </h3>
                
                <?php if ($replies->num_rows > 0): ?>
                    <div class="reply-list">
                        <?php $reply_num = 1; ?>
                        <?php while ($reply = $replies->fetch_assoc()): ?>
                            <div class="reply-item">
                                <div class="reply-header">
                                    <span class="reply-number">#<?php echo $reply_num++; ?></span>
                                    <span class="author">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars($reply['username']); ?>
                                    </span>
                                    <span class="time">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="reply-content">
                                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-replies">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 12px;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <p>暂无回复，快来抢沙发吧！</p>
                    </div>
                <?php endif; ?>

                <div class="reply-form">
                    <h4>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="12" x2="10" y2="12"></line>
                            <line x1="22" y1="6" x2="10" y2="6"></line>
                            <line x1="22" y1="18" x2="10" y2="18"></line>
                            <line x1="7" y1="6" x2="2" y2="6"></line>
                            <line x1="7" y1="12" x2="2" y2="12"></line>
                            <line x1="7" y1="18" x2="2" y2="18"></line>
                        </svg>
                        发表回复
                    </h4>
                    
                    <?php if ($reply_error): ?>
                        <div class="alert alert-error">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            <?php echo $reply_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <textarea name="content" rows="4" required 
                                      placeholder="写下你的想法..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                            发表回复
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> 校园论坛 - 交流学习，共同进步</p>
        </div>
    </footer>
</body>
</html>

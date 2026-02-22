<?php
require_once __DIR__ . '/includes/functions.php';

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$post = getPost($postId);

if (!$post) {
    redirect('index.php');
}

// 增加浏览量
increaseViews($postId);

// 获取评论
$comments = getComments($postId);

// 处理评论提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $content = trim($_POST['content'] ?? '');
    $parentId = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    
    if (empty($content)) {
        $error = '请输入评论内容';
    } elseif (strlen($content) > 1000) {
        $error = '评论内容不能超过1000字';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$postId, $_SESSION['user_id'], $parentId, $content])) {
            $success = '评论发布成功！';
            // 刷新页面以显示新评论
            header("Refresh: 1; URL=post.php?id=" . $postId);
        } else {
            $error = '评论发布失败，请稍后重试';
        }
    }
}

$pageTitle = $post['title'];
include __DIR__ . '/includes/header.php';
?>

<div class="post-detail-container">
    <!-- 帖子内容 -->
    <article class="post-detail">
        <div class="post-detail-header">
            <div class="post-meta">
                <a href="category.php?id=<?php echo $post['category_id']; ?>" class="post-category-badge">
                    <i class="fas fa-<?php echo $post['category_icon'] ?? 'folder'; ?>"></i>
                    <?php echo clean($post['category_name']); ?>
                </a>
                <?php if ($post['is_top']): ?>
                <span class="badge badge-top"><i class="fas fa-thumbtack"></i> 置顶</span>
                <?php endif; ?>
                <?php if ($post['is_essence']): ?>
                <span class="badge badge-essence"><i class="fas fa-gem"></i> 精华</span>
                <?php endif; ?>
            </div>
            <h1 class="post-detail-title"><?php echo clean($post['title']); ?></h1>
            <div class="post-detail-meta">
                <div class="post-author-info">
                    <a href="user_profile.php?id=<?php echo $post['user_id']; ?>">
                        <img src="uploads/avatars/<?php echo $post['avatar']; ?>" alt="<?php echo clean($post['nickname'] ?: $post['username']); ?>">
                    </a>
                    <div class="author-details">
                        <a href="user_profile.php?id=<?php echo $post['user_id']; ?>" class="author-name"><?php echo clean($post['nickname'] ?: $post['username']); ?></a>
                        <span class="post-time"><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?> 发布</span>
                    </div>
                </div>
                <div class="post-stats">
                    <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?> 浏览</span>
                </div>
            </div>
        </div>
        
        <div class="post-detail-content<?php echo !empty($post['content_html']) ? ' post-content-html' : ''; ?>">
            <?php
            if (!empty($post['content_html'])) {
                echo $post['content_html'];
            } else {
                echo nl2br(clean($post['content']));
            }
            ?>
        </div>
        
        <div class="post-detail-footer">
            <?php if ($currentUser && ($currentUser['id'] == $post['user_id'] || isAdmin())): ?>
            <div class="post-actions">
                <a href="post-edit.php?id=<?php echo $postId; ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-edit"></i> 编辑
                </a>
                <a href="post-delete.php?id=<?php echo $postId; ?>" class="btn btn-danger btn-sm" 
                   onclick="return confirm('确定要删除这个帖子吗？')">
                    <i class="fas fa-trash"></i> 删除
                </a>
            </div>
            <?php endif; ?>
        </div>
    </article>
    
    <!-- 评论区 -->
    <section class="comments-section">
        <h3><i class="fas fa-comments"></i> 评论 (<?php echo count($comments); ?>)</h3>
        
        <?php if (isLoggedIn()): ?>
        <div class="comment-form-wrapper">
            <?php if ($error): ?>
                <?php echo showError($error); ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?php echo showSuccess($success); ?>
            <?php endif; ?>
            
            <form method="POST" action="" class="comment-form">
                <div class="form-group">
                    <textarea name="content" rows="4" placeholder="写下你的评论..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> 发表评论
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="login-to-comment">
            <p>请 <a href="login.php">登录</a> 后发表评论</p>
        </div>
        <?php endif; ?>
        
        <!-- 评论列表 -->
        <div class="comments-list">
            <?php if (empty($comments)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <p>暂无评论，来发表第一条评论吧！</p>
            </div>
            <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                <div class="comment-avatar">
                    <img src="uploads/avatars/<?php echo $comment['avatar']; ?>" alt="<?php echo clean($comment['nickname'] ?: $comment['username']); ?>">
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-author"><?php echo clean($comment['nickname'] ?: $comment['username']); ?></span>
                        <span class="comment-time"><?php echo timeAgo($comment['created_at']); ?></span>
                    </div>
                    <div class="comment-body">
                        <?php echo nl2br(clean($comment['content'])); ?>
                    </div>
                    <div class="comment-actions">
                        <?php if (isLoggedIn()): ?>
                        <button class="btn-reply" onclick="showReplyForm(<?php echo $comment['id']; ?>)">
                            <i class="fas fa-reply"></i> 回复
                        </button>
                        <?php endif; ?>
                        <?php if ($currentUser && ($currentUser['id'] == $comment['user_id'] || isAdmin())): ?>
                        <a href="comment-delete.php?id=<?php echo $comment['id']; ?>&post_id=<?php echo $postId; ?>" 
                           class="btn-delete" onclick="return confirm('确定要删除这条评论吗？')">
                            <i class="fas fa-trash"></i> 删除
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 回复表单 -->
                    <div class="reply-form-wrapper" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                        <form method="POST" action="" class="reply-form">
                            <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                            <textarea name="content" rows="3" placeholder="回复 <?php echo clean($comment['nickname'] ?: $comment['username']); ?>..." required></textarea>
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline btn-sm" onclick="hideReplyForm(<?php echo $comment['id']; ?>)">取消</button>
                                <button type="submit" class="btn btn-primary btn-sm">回复</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- 回复列表 -->
                    <?php
                    $replies = getReplies($comment['id']);
                    if (!empty($replies)):
                    ?>
                    <div class="replies-list">
                        <?php foreach ($replies as $reply): ?>
                        <div class="reply-item" id="comment-<?php echo $reply['id']; ?>">
                            <div class="reply-avatar">
                                <img src="uploads/avatars/<?php echo $reply['avatar']; ?>" alt="<?php echo clean($reply['nickname'] ?: $reply['username']); ?>">
                            </div>
                            <div class="reply-content">
                                <div class="reply-header">
                                    <span class="reply-author"><?php echo clean($reply['nickname'] ?: $reply['username']); ?></span>
                                    <span class="reply-time"><?php echo timeAgo($reply['created_at']); ?></span>
                                </div>
                                <div class="reply-body">
                                    <?php echo nl2br(clean($reply['content'])); ?>
                                </div>
                                <?php if ($currentUser && ($currentUser['id'] == $reply['user_id'] || isAdmin())): ?>
                                <div class="reply-actions">
                                    <a href="comment-delete.php?id=<?php echo $reply['id']; ?>&post_id=<?php echo $postId; ?>" 
                                       class="btn-delete" onclick="return confirm('确定要删除这条回复吗？')">
                                        <i class="fas fa-trash"></i> 删除
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
function showReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).style.display = 'block';
}

function hideReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).style.display = 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

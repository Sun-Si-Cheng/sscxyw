<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$commentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    redirect('post.php?id=' . $postId);
}

$currentUser = getCurrentUser();
if ($currentUser['id'] != $comment['user_id'] && !isAdmin()) {
    redirect('post.php?id=' . $postId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die('无效请求');
    }
    
    $stmt = $pdo->prepare("UPDATE comments SET status = 0 WHERE id = ?");
    $stmt->execute([$commentId]);
    
    redirect('post.php?id=' . $postId);
}

$pageTitle = '删除评论';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2><i class="fas fa-trash"></i> 删除评论</h2>
        <div class="alert alert-danger">
            确定要删除这条评论吗？此操作不可撤销。
        </div>
        <div style="background: var(--bg-color); padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
            <?php echo nl2br(clean($comment['content'])); ?>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-actions">
                <a href="post.php?id=<?php echo $postId; ?>" class="btn btn-outline">取消</a>
                <button type="submit" class="btn btn-danger">确认删除</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

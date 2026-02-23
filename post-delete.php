<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect("login.php");
}

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$post = getPost($postId);

if (!$post) {
    redirect("index.php");
}

$currentUser = getCurrentUser();
if ($currentUser['id'] != $post['user_id'] && !isAdmin()) {
    redirect("post.php?id=" . $postId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die('无效请求');
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE posts SET status = 0 WHERE id = ?");
    $stmt->execute([$postId]);
    
    redirect("category.php?id=" . $post['category_id']);
}

$pageTitle = '删除帖子';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2><i class="fas fa-trash"></i> 删除帖子</h2>
        <div class="alert alert-danger">
            确定要删除帖子「<?php echo clean($post['title']); ?>」吗？此操作不可撤销。
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

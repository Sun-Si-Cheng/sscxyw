<?php
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = POSTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// 获取用户的帖子
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.icon as category_icon,
                      (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 1) as comment_count
                      FROM posts p
                      JOIN categories c ON p.category_id = c.id
                      WHERE p.user_id = ? AND p.status = 1
                      ORDER BY p.created_at DESC
                      LIMIT ? OFFSET ?");
$stmt->execute([$currentUser['id'], $perPage, $offset]);
$posts = $stmt->fetchAll();

// 获取帖子总数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 1");
$stmt->execute([$currentUser['id']]);
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

$pageTitle = '我的帖子';
include __DIR__ . '/includes/header.php';
?>

<div class="my-posts-container">
    <div class="page-header">
        <h2><i class="fas fa-file-alt"></i> 我的帖子</h2>
        <a href="post-create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> 发布新帖
        </a>
    </div>
    
    <?php if (empty($posts)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>你还没有发布过帖子</p>
        <a href="post-create.php" class="btn btn-primary">发布第一个帖子</a>
    </div>
    <?php else: ?>
    <div class="posts-table-wrapper">
        <table class="posts-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>板块</th>
                    <th>发布时间</th>
                    <th>浏览</th>
                    <th>评论</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td class="post-title-cell">
                        <?php if ($post['is_top']): ?>
                        <span class="badge badge-top">置顶</span>
                        <?php endif; ?>
                        <?php if ($post['is_essence']): ?>
                        <span class="badge badge-essence">精华</span>
                        <?php endif; ?>
                        <a href="post.php?id=<?php echo $post['id']; ?>"><?php echo clean($post['title']); ?></a>
                    </td>
                    <td>
                        <a href="category.php?id=<?php echo $post['category_id']; ?>">
                            <i class="fas fa-<?php echo $post['category_icon']; ?>"></i>
                            <?php echo clean($post['category_name']); ?>
                        </a>
                    </td>
                    <td><?php echo timeAgo($post['created_at']); ?></td>
                    <td><?php echo $post['views']; ?></td>
                    <td><?php echo $post['comment_count']; ?></td>
                    <td class="actions-cell">
                        <a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline" title="编辑">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="post-delete.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('确定要删除这个帖子吗？')" title="删除">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline">
            <i class="fas fa-chevron-left"></i> 上一页
        </a>
        <?php endif; ?>
        
        <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</span>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline">
            下一页 <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

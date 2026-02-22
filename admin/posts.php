<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$q = trim($_GET['q'] ?? '');
$statusFilter = isset($_GET['status']) ? (int) $_GET['status'] : 1;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.id, p.user_id, p.title, p.status, p.is_top, p.views, p.created_at, u.username, u.nickname
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE 1=1";
$params = [];
if ($statusFilter >= 0) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
}
$like = $q !== '' ? '%' . $q . '%' : '';
if ($q !== '') {
    $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = $like;
    $params[] = $like;
}
$sql .= " ORDER BY p.is_top DESC, p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$countParams = [];
if ($statusFilter >= 0) $countParams[] = $statusFilter;
if ($q !== '') {
    $countParams[] = $like;
    $countParams[] = $like;
}
$countSql = "SELECT COUNT(*) FROM posts p WHERE 1=1";
if ($statusFilter >= 0) $countSql .= " AND p.status = ?";
if ($q !== '') $countSql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
$stmt = $pdo->prepare($countSql);
$stmt->execute($countParams);
$total = (int) $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['post_id'])) {
    $pid = (int) $_POST['post_id'];
    if ($pid) {
        if ($_POST['action'] === 'delete') {
            $pdo->prepare("UPDATE posts SET status = 0 WHERE id = ?")->execute([$pid]);
        } elseif ($_POST['action'] === 'toggle_top') {
            $pdo->prepare("UPDATE posts SET is_top = 1 - is_top WHERE id = ?")->execute([$pid]);
        }
        header('Location: posts.php?' . http_build_query(['q' => $q, 'status' => $statusFilter, 'page' => $page]));
        exit;
    }
}

$pageTitle = '帖子管理';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">帖子管理</h1>

<div class="admin-card">
    <form method="GET" style="margin-bottom: 1rem;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="搜索标题">
        <select name="status">
            <option value="1" <?php echo $statusFilter === 1 ? 'selected' : ''; ?>>正常</option>
            <option value="0" <?php echo $statusFilter === 0 ? 'selected' : ''; ?>>已删除</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">搜索</button>
    </form>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>标题</th>
                <th>作者</th>
                <th>浏览</th>
                <th>置顶</th>
                <th>状态</th>
                <th>时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><a href="../post.php?id=<?php echo $p['id']; ?>" target="_blank"><?php echo htmlspecialchars(mb_substr($p['title'], 0, 30)); ?></a></td>
                <td><?php echo htmlspecialchars($p['nickname'] ?: $p['username']); ?></td>
                <td><?php echo $p['views']; ?></td>
                <td><?php echo $p['is_top'] ? '是' : '否'; ?></td>
                <td><?php echo $p['status'] ? '正常' : '删除'; ?></td>
                <td><?php echo $p['created_at']; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="action" value="toggle_top">
                        <button type="submit" class="btn btn-sm btn-outline"><?php echo $p['is_top'] ? '取消置顶' : '置顶'; ?></button>
                    </form>
                    <?php if ($p['status']): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除该帖子？');">
                        <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrap" style="margin-top: 1rem;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?q=<?php echo urlencode($q); ?>&status=<?php echo $statusFilter; ?>&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

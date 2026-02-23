<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT id, username, nickname, email, avatar, role, status, created_at FROM users WHERE 1=1";
$params = [];
$like = '';
if ($q !== '') {
    $sql .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
$sql .= " ORDER BY id DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM users WHERE 1=1";
$countParams = [];
if ($q !== '') {
    $countSql .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ?)";
    $countParams = [$like, $like, $like];
}
$stmt = $pdo->prepare($countSql);
$stmt->execute($countParams);
$total = (int) $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 操作：禁用/启用
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die('无效请求');
    }
    $uid = (int) $_POST['user_id'];
    if ($uid && $uid != getCurrentUser()['id']) {
        if ($_POST['action'] === 'toggle_status') {
            $stmt = $pdo->prepare("UPDATE users SET status = 1 - status WHERE id = ?");
            $stmt->execute([$uid]);
            if (function_exists('admin_log')) {
                admin_log(getCurrentUser()['id'], 'toggle_user_status', 'user', $uid);
            }
            header('Location: users.php?q=' . urlencode($q) . '&page=' . $page);
            exit;
        }
    }
}

$pageTitle = '用户管理';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">用户管理</h1>

<div class="admin-card">
    <form method="GET" action="" style="margin-bottom: 1rem;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="搜索用户名/昵称/邮箱">
        <button type="submit" class="btn btn-primary btn-sm">搜索</button>
    </form>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>昵称</th>
                <th>邮箱</th>
                <th>角色</th>
                <th>状态</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['nickname'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                <td><?php echo $u['role']; ?></td>
                <td><?php echo $u['status'] ? '正常' : '禁用'; ?></td>
                <td><?php echo $u['created_at']; ?></td>
                <td>
                    <?php if ($u['id'] != getCurrentUser()['id']): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <button type="submit" class="btn btn-sm <?php echo $u['status'] ? 'btn-danger' : 'btn-outline'; ?>">
                            <?php echo $u['status'] ? '禁用' : '启用'; ?>
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted">当前用户</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrap" style="margin-top: 1rem;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

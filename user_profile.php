<?php
require_once __DIR__ . '/includes/functions.php';

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$profileUser = $userId > 0 ? getUserById($userId) : null;

if (!$profileUser) {
    redirect('index.php');
}

$currentUser = getCurrentUser();
$counts = getFollowCounts($userId);
$following = $currentUser ? isFollowing($currentUser['id'], $userId) : false;

// 该用户最近帖子
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT p.id, p.title, p.created_at, c.name as category_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.user_id = ? AND p.status = 1 ORDER BY p.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$userPosts = $stmt->fetchAll();

$pageTitle = clean($profileUser['nickname'] ?: $profileUser['username']) . ' 的主页';
include __DIR__ . '/includes/header.php';
?>

<div class="profile-container">
    <div class="profile-layout">
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar-wrapper">
                    <img src="uploads/avatars/<?php echo $profileUser['avatar']; ?>" alt="" class="profile-avatar-lg">
                </div>
                <h3 class="profile-name"><?php echo clean($profileUser['nickname'] ?: $profileUser['username']); ?></h3>
                <p class="profile-username">@<?php echo clean($profileUser['username']); ?></p>
                <p class="profile-signature"><?php echo clean($profileUser['signature'] ?: '这个人很懒，什么都没写~'); ?></p>
                <div class="profile-meta">
                    <div class="meta-item">
                        <span class="meta-label">注册时间</span>
                        <span class="meta-value"><?php echo date('Y-m-d', strtotime($profileUser['created_at'])); ?></span>
                    </div>
                </div>
                <div class="profile-follow-stats">
                    <a href="followers.php?id=<?php echo $userId; ?>"><strong><?php echo (int) $counts['followers']; ?></strong> 粉丝</a>
                    <a href="following.php?id=<?php echo $userId; ?>"><strong><?php echo (int) $counts['following']; ?></strong> 关注</a>
                </div>
                <?php if ($currentUser && $currentUser['id'] != $userId): ?>
                <div class="profile-actions">
                    <button type="button" class="btn btn-primary btn-follow" data-user-id="<?php echo $userId; ?>" data-following="<?php echo $following ? '1' : '0'; ?>">
                        <i class="fas fa-user-<?php echo $following ? 'minus' : 'plus'; ?>"></i>
                        <span><?php echo $following ? '已关注' : '关注'; ?></span>
                    </button>
                    <a href="messages.php?to=<?php echo $userId; ?>" class="btn btn-outline"><i class="fas fa-envelope"></i> 发消息</a>
                </div>
                <?php elseif ($currentUser && $currentUser['id'] == $userId): ?>
                <div class="profile-actions">
                    <a href="profile.php" class="btn btn-primary"><i class="fas fa-edit"></i> 编辑资料</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-main">
            <div class="profile-form-card">
                <h3><i class="fas fa-file-alt"></i> 最近帖子</h3>
                <?php if (empty($userPosts)): ?>
                <p class="text-muted">暂无帖子</p>
                <?php else: ?>
                <ul class="user-posts-list">
                    <?php foreach ($userPosts as $p): ?>
                    <li>
                        <a href="post.php?id=<?php echo $p['id']; ?>"><?php echo clean($p['title']); ?></a>
                        <span class="post-meta-inline"><?php echo $p['category_name']; ?> · <?php echo timeAgo($p['created_at']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var btn = document.querySelector('.btn-follow');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var userId = this.getAttribute('data-user-id');
        var following = this.getAttribute('data-following') === '1';
        var self = this;
        var fd = new FormData();
        fd.append('target_user_id', userId);
        fetch('api/follow/toggle.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.error) { alert(res.error); return; }
                self.setAttribute('data-following', res.followed ? '1' : '0');
                self.querySelector('i').className = 'fas fa-user-' + (res.followed ? 'minus' : 'plus');
                self.querySelector('span').textContent = res.followed ? '已关注' : '关注';
            });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/functions.php';

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$profileUser = $userId > 0 ? getUserById($userId) : null;
if (!$profileUser) {
    redirect('index.php');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$list = getFollowList($userId, 'followers', $page, $perPage);
$total = getFollowListCount($userId, 'followers');
$totalPages = ceil($total / $perPage);

$pageTitle = clean($profileUser['nickname'] ?: $profileUser['username']) . ' 的粉丝';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <div class="profile-form-card">
        <h3><i class="fas fa-users"></i> 粉丝列表</h3>
        <p class="text-muted"><a href="user_profile.php?id=<?php echo $userId; ?>"><?php echo clean($profileUser['nickname'] ?: $profileUser['username']); ?></a> 的粉丝 (<?php echo $total; ?>)</p>
        <?php if (empty($list)): ?>
        <p class="text-muted">暂无粉丝</p>
        <?php else: ?>
        <ul class="follow-list">
            <?php foreach ($list as $u): ?>
            <li class="follow-item">
                <a href="user_profile.php?id=<?php echo $u['id']; ?>" class="follow-avatar">
                    <img src="<?php echo getAvatarUrl($u['avatar']); ?>" alt="">
                </a>
                <div class="follow-info">
                    <a href="user_profile.php?id=<?php echo $u['id']; ?>"><?php echo clean($u['nickname'] ?: $u['username']); ?></a>
                    <span class="follow-username">@<?php echo clean($u['username']); ?></span>
                </div>
                <?php if (isLoggedIn() && getCurrentUser()['id'] != $u['id']): ?>
                <?php $following = isFollowing(getCurrentUser()['id'], $u['id']); ?>
                <button type="button" class="btn btn-sm <?php echo $following ? 'btn-outline' : 'btn-primary'; ?> btn-follow-inline" data-user-id="<?php echo $u['id']; ?>" data-following="<?php echo $following ? '1' : '0'; ?>">
                    <?php echo $following ? '已关注' : '关注'; ?>
                </button>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrap">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?id=<?php echo $userId; ?>&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    document.querySelectorAll('.btn-follow-inline').forEach(function(btn) {
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
                    self.textContent = res.followed ? '已关注' : '关注';
                    self.className = 'btn btn-sm ' + (res.followed ? 'btn-outline' : 'btn-primary') + ' btn-follow-inline';
                });
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

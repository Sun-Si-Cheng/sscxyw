<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = (int) getCurrentUser()['id'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$list = getNotifications($userId, $page, $perPage);
$total = getNotificationsCount($userId);
$totalPages = ceil($total / $perPage);

$typeLabels = [
    'new_message' => '新消息',
    'new_follower' => '新粉丝',
    'system' => '系统通知',
    'post_reply' => '评论回复',
];

$pageTitle = '通知中心';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 640px; margin: 2rem auto;">
    <div class="profile-form-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3><i class="fas fa-bell"></i> 通知中心</h3>
            <?php if ($total > 0): ?>
            <button type="button" class="btn btn-outline btn-sm" id="markAllRead">全部标为已读</button>
            <?php endif; ?>
        </div>
        <?php if (empty($list)): ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <p>暂无通知</p>
        </div>
        <?php else: ?>
        <ul class="notification-list">
            <?php foreach ($list as $n): ?>
            <li class="notification-item <?php echo $n['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $n['id']; ?>">
                <div class="notification-body">
                    <span class="notification-type"><?php echo $typeLabels[$n['type']] ?? $n['type']; ?></span>
                    <?php
                    $data = $n['data'] ? json_decode($n['data'], true) : [];
                    if ($n['type'] === 'new_follower' && !empty($data['user_id'])):
                        $u = getUserById($data['user_id']);
                        if ($u):
                    ?>
                    <a href="user_profile.php?id=<?php echo $u['id']; ?>"><?php echo clean($u['nickname'] ?: $u['username']); ?></a> 关注了你
                    <?php endif; endif; ?>
                    <?php if ($n['type'] === 'new_message'): ?>
                    收到新私信 <?php if (!empty($data['conversation_id'])): ?><a href="messages.php?id=<?php echo (int)$data['conversation_id']; ?>">查看</a><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($n['type'] === 'system' && $n['data']): ?>
                    <?php $d = json_decode($n['data'], true); echo clean($d['text'] ?? $n['data']); ?>
                    <?php endif; ?>
                </div>
                <span class="notification-time"><?php echo timeAgo($n['created_at']); ?></span>
                <?php if (!$n['is_read']): ?>
                <button type="button" class="btn btn-sm btn-outline mark-read-btn">标为已读</button>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrap" style="margin-top: 1rem;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    document.querySelectorAll('.mark-read-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var li = this.closest('.notification-item');
            var id = li.getAttribute('data-id');
            var fd = new FormData();
            fd.append('id', id);
            fetch('api/notifications/mark_read.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function() {
                    li.classList.remove('unread');
                    btn.remove();
                });
        });
    });
    var markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', function() {
            fetch('api/notifications/mark_read.php', { method: 'POST', body: new FormData(), credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function() {
                    document.querySelectorAll('.notification-item').forEach(function(li) {
                        li.classList.remove('unread');
                        var btn = li.querySelector('.mark-read-btn');
                        if (btn) btn.remove();
                    });
                    markAll.remove();
                });
        });
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

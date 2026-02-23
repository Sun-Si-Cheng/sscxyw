<?php
require_once __DIR__ . '/includes/functions.php';

$q = trim($_GET['q'] ?? '');
$scope = isset($_GET['scope']) && in_array($_GET['scope'], ['post', 'user']) ? $_GET['scope'] : 'post';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$postResult = ['items' => [], 'total' => 0];
$userResult = ['items' => [], 'total' => 0];

if ($q !== '') {
    if ($scope === 'post') {
        $postResult = searchPosts($q, $page, $perPage);
    } else {
        $userResult = searchUsers($q, $page, $perPage);
    }
}

$pageTitle = $q !== '' ? '搜索: ' . $q : '搜索';
include __DIR__ . '/includes/header.php';
?>

<div class="container search-page">
    <div class="search-box-card">
        <form method="GET" action="search.php" class="search-form-inline">
            <input type="text" name="q" value="<?php echo clean($q); ?>" placeholder="搜索帖子或用户..." class="search-input" required>
            <input type="hidden" name="scope" value="<?php echo $scope; ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 搜索</button>
        </form>
        <div class="search-tabs">
            <a href="?q=<?php echo urlencode($q); ?>&scope=post" class="tab <?php echo $scope === 'post' ? 'active' : ''; ?>">帖子</a>
            <a href="?q=<?php echo urlencode($q); ?>&scope=user" class="tab <?php echo $scope === 'user' ? 'active' : ''; ?>">用户</a>
        </div>
    </div>

    <?php if ($q === ''): ?>
    <div class="search-empty">
        <i class="fas fa-search"></i>
        <p>输入关键词搜索帖子或用户</p>
    </div>
    <?php else: ?>
    <?php if ($scope === 'post'): ?>
    <div class="search-result-header">
        <h3>帖子 (<?php echo $postResult['total']; ?>)</h3>
    </div>
    <?php if (empty($postResult['items'])): ?>
    <div class="empty-state">
        <i class="fas fa-file-alt"></i>
        <p>未找到相关帖子</p>
    </div>
    <?php else: ?>
    <ul class="search-posts-list">
        <?php foreach ($postResult['items'] as $p): ?>
        <li class="search-post-item">
            <a href="post.php?id=<?php echo $p['id']; ?>" class="search-post-title"><?php echo clean($p['title']); ?></a>
            <div class="search-post-meta">
                <a href="user_profile.php?id=<?php echo $p['user_id']; ?>"><?php echo clean($p['nickname'] ?: $p['username']); ?></a>
                <?php if (!empty($p['category_name'])): ?> · <?php echo clean($p['category_name']); ?><?php endif; ?>
                · <?php echo timeAgo($p['created_at']); ?>
                · <?php echo (int)$p['views']; ?> 浏览
            </div>
            <?php if (!empty($p['content_text'])): ?>
            <p class="search-post-excerpt"><?php echo clean(truncateText($p['content_text'], 120)); ?></p>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
    $totalPages = ceil($postResult['total'] / $perPage);
    if ($totalPages > 1):
    ?>
    <div class="pagination-wrap" style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?q=<?php echo urlencode($q); ?>&scope=post&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php else: ?>
    <div class="search-result-header">
        <h3>用户 (<?php echo $userResult['total']; ?>)</h3>
    </div>
    <?php if (empty($userResult['items'])): ?>
    <div class="empty-state">
        <i class="fas fa-user"></i>
        <p>未找到相关用户</p>
    </div>
    <?php else: ?>
    <ul class="search-users-list">
        <?php foreach ($userResult['items'] as $u): ?>
        <li class="follow-item">
            <a href="user_profile.php?id=<?php echo $u['id']; ?>" class="follow-avatar">
                <img src="<?php echo getAvatarUrl($u['avatar']); ?>" alt="">
            </a>
            <div class="follow-info">
                <a href="user_profile.php?id=<?php echo $u['id']; ?>"><?php echo clean($u['nickname'] ?: $u['username']); ?></a>
                <span class="follow-username">@<?php echo clean($u['username']); ?></span>
            </div>
            <?php if (isLoggedIn() && getCurrentUser()['id'] != $u['id']): ?>
            <a href="user_profile.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline">查看</a>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
    $totalPages = ceil($userResult['total'] / $perPage);
    if ($totalPages > 1):
    ?>
    <div class="pagination-wrap" style="margin-top:1rem;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?q=<?php echo urlencode($q); ?>&scope=user&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

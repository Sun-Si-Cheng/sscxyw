<?php
require_once __DIR__ . '/includes/functions.php';

$categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category = getCategory($categoryId);

if (!$category) {
    redirect('index.php');
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$posts = getPosts($categoryId, $page);
$totalPosts = getPostsCount($categoryId);
$totalPages = ceil($totalPosts / POSTS_PER_PAGE);

$pageTitle = $category['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="category-container">
    <!-- 板块头部 -->
    <section class="category-header">
        <div class="category-header-content">
            <div class="category-icon-large">
                <i class="fas fa-<?php echo $category['icon']; ?>"></i>
            </div>
            <div class="category-header-info">
                <h1><?php echo clean($category['name']); ?></h1>
                <p><?php echo clean($category['description']); ?></p>
                <div class="category-stats">
                    <span><i class="fas fa-file-alt"></i> <?php echo $totalPosts; ?> 个帖子</span>
                </div>
            </div>
            <?php if (isLoggedIn()): ?>
            <div class="category-actions">
                <a href="post-create.php?category=<?php echo $categoryId; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i> 发布帖子
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <div class="content-wrapper">
        <div class="main-column">
            <section class="posts-section">
                <div class="section-header">
                    <h3>帖子列表</h3>
                    <div class="filter-options">
                        <a href="?id=<?php echo $categoryId; ?>&sort=new" class="<?php echo !isset($_GET['sort']) || $_GET['sort'] == 'new' ? 'active' : ''; ?>">最新</a>
                        <a href="?id=<?php echo $categoryId; ?>&sort=hot" class="<?php echo isset($_GET['sort']) && $_GET['sort'] == 'hot' ? 'active' : ''; ?>">热门</a>
                    </div>
                </div>
                
                <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>该板块暂无帖子</p>
                    <?php if (isLoggedIn()): ?>
                    <a href="post-create.php?category=<?php echo $categoryId; ?>" class="btn btn-primary">发布第一个帖子</a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="posts-list">
                    <?php foreach ($posts as $post): ?>
                    <article class="post-card <?php echo $post['is_top'] ? 'is-top' : ''; ?> <?php echo $post['is_essence'] ? 'is-essence' : ''; ?>">
                        <?php if ($post['is_top']): ?>
                        <span class="badge badge-top"><i class="fas fa-thumbtack"></i> 置顶</span>
                        <?php endif; ?>
                        <?php if ($post['is_essence']): ?>
                        <span class="badge badge-essence"><i class="fas fa-gem"></i> 精华</span>
                        <?php endif; ?>
                        
                        <h4 class="post-title">
                            <a href="post.php?id=<?php echo $post['id']; ?>"><?php echo clean($post['title']); ?></a>
                        </h4>
                        
                        <p class="post-excerpt"><?php echo clean(truncateText(strip_tags($post['content']), 150)); ?></p>
                        
                        <div class="post-footer">
                            <div class="post-author">
                                <a href="user_profile.php?id=<?php echo $post['user_id']; ?>">
                                    <img src="uploads/avatars/<?php echo $post['avatar']; ?>" alt="<?php echo clean($post['nickname'] ?: $post['username']); ?>">
                                    <span><?php echo clean($post['nickname'] ?: $post['username']); ?></span>
                                </a>
                                <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
                            </div>
                            <div class="post-stats">
                                <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?></span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline">
                        <i class="fas fa-chevron-left"></i> 上一页
                    </a>
                    <?php endif; ?>
                    
                    <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</span>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline">
                        下一页 <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
        
        <aside class="sidebar">
            <div class="widget">
                <h4><i class="fas fa-th-large"></i> 其他板块</h4>
                <ul class="category-list">
                    <?php foreach (getCategories() as $cat): ?>
                    <?php if ($cat['id'] != $categoryId): ?>
                    <li>
                        <a href="category.php?id=<?php echo $cat['id']; ?>">
                            <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                            <?php echo clean($cat['name']); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

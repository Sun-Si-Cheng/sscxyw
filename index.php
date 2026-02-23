<?php
require_once __DIR__ . '/includes/functions.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$posts = getPosts(null, $page);
$totalPosts = getPostsCount();
$totalPages = ceil($totalPosts / POSTS_PER_PAGE);
$categories = getCategories();

$pageTitle = '首页';
include __DIR__ . '/includes/header.php';
?>

<div class="home-container">
    <!-- 欢迎横幅 -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>欢迎来到<?php echo SITE_NAME; ?></h1>
            <p><?php echo SITE_DESCRIPTION; ?></p>
            <?php if (!isLoggedIn()): ?>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary btn-lg">立即加入</a>
                <a href="login.php" class="btn btn-outline btn-lg">登录</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <div class="content-wrapper">
        <!-- 左侧主要内容 -->
        <div class="main-column">
            <!-- 板块导航 -->
            <section class="categories-section">
                <h3><i class="fas fa-th-large"></i> 论坛板块</h3>
                <div class="categories-grid">
                    <?php foreach ($categories as $cat): ?>
                    <a href="category.php?id=<?php echo $cat['id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                        </div>
                        <div class="category-info">
                            <h4><?php echo clean($cat['name']); ?></h4>
                            <p><?php echo clean($cat['description']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- 最新帖子 -->
            <section class="posts-section">
                <div class="section-header">
                    <h3><i class="fas fa-fire"></i> 最新帖子</h3>
                    <?php if (isLoggedIn()): ?>
                    <a href="post-create.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 发布帖子
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>暂无帖子，来发布第一个帖子吧！</p>
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
                        
                        <div class="post-header">
                            <a href="category.php?id=<?php echo $post['category_id']; ?>" class="post-category">
                                <i class="fas fa-<?php echo $post['category_icon']; ?>"></i>
                                <?php echo clean($post['category_name']); ?>
                            </a>
                            <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
                        </div>
                        
                        <h4 class="post-title">
                            <a href="post.php?id=<?php echo $post['id']; ?>"><?php echo clean($post['title']); ?></a>
                        </h4>
                        
                        <p class="post-excerpt"><?php echo clean(truncateText(stripHtmlToText($post['content_html'] ?? $post['content']), 150)); ?></p>
                        
                        <div class="post-footer">
                            <div class="post-author">
                                <a href="user_profile.php?id=<?php echo $post['user_id']; ?>">
                                    <img src="<?php echo getAvatarUrl($post['avatar']); ?>" alt="<?php echo clean($post['nickname'] ?: $post['username']); ?>">
                                    <span><?php echo clean($post['nickname'] ?: $post['username']); ?></span>
                                </a>
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
            </section>
        </div>
        
        <!-- 右侧侧边栏 -->
        <aside class="sidebar">
            <!-- 用户信息 -->
            <?php if ($currentUser): ?>
            <div class="widget user-widget">
                <div class="user-card">
                    <img src="<?php echo getAvatarUrl($currentUser['avatar']); ?>" alt="<?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?>" class="user-avatar-lg">
                    <h4><?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?></h4>
                    <p class="user-signature"><?php echo clean($currentUser['signature'] ?: '这个人很懒，什么都没写~'); ?></p>
                    <div class="user-actions">
                        <a href="profile.php" class="btn btn-outline btn-sm">个人中心</a>
                        <a href="my-posts.php" class="btn btn-primary btn-sm">我的帖子</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="widget login-widget">
                <h4>欢迎访问</h4>
                <p>登录后可以发布帖子、参与讨论</p>
                <div class="widget-actions">
                    <a href="login.php" class="btn btn-primary btn-block">登录</a>
                    <a href="register.php" class="btn btn-outline btn-block">注册账号</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 热门板块 -->
            <div class="widget">
                <h4><i class="fas fa-fire"></i> 热门板块</h4>
                <ul class="category-list">
                    <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                    <li>
                        <a href="category.php?id=<?php echo $cat['id']; ?>">
                            <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                            <?php echo clean($cat['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

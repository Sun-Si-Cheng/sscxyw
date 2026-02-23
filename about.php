<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '关于我们';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="post-form-container" style="max-width: 800px; margin-top: 2rem;">
        <div class="post-form-box">
            <h2 style="margin-bottom: 1rem;">
                <i class="fas fa-info-circle"></i> 关于本站
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">
                <?php echo SITE_NAME; ?> 是为校园师生打造的交流与分享社区，支持学习讨论、生活分享、二手交易等多种场景。
            </p>
            <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">
                你可以在这里发布帖子、评论互动、关注感兴趣的同学，与校园伙伴一起构建一个友好、积极的社区环境。
            </p>
            <p style="color: var(--text-secondary);">
                如有任何建议或问题，欢迎通过「联系我们」页面反馈，我们会持续改进体验。
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


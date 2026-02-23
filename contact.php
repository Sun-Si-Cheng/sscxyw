<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '联系我们';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="post-form-container" style="max-width: 800px; margin-top: 2rem;">
        <div class="post-form-box">
            <h2 style="margin-bottom: 1rem;">
                <i class="fas fa-envelope"></i> 联系我们
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">
                如遇到账号问题、功能异常或有产品建议，欢迎通过以下方式联系我们：
            </p>
            <ul style="color: var(--text-secondary); padding-left: 1.25rem; margin-bottom: 0.75rem;">
                <li>邮箱：<a href="mailto:contact@campus.edu">contact@campus.edu</a></li>
                <li>电话：010-12345678（工作日 9:00-18:00）</li>
            </ul>
            <p style="color: var(--text-secondary);">
                为了更快帮助你排查问题，建议在反馈时附上尽可能详细的说明（操作步骤、页面地址、截图等）。
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


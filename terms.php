<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '用户协议';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="post-form-container" style="max-width: 900px; margin-top: 2rem;">
        <div class="post-form-box">
            <h2 style="margin-bottom: 1rem;">
                <i class="fas fa-file-contract"></i> 用户协议（简要）
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">
                使用 <?php echo SITE_NAME; ?> 即表示你已阅读并同意本协议的相关条款，请在注册或使用前仔细阅读。
            </p>
            <ol style="color: var(--text-secondary); padding-left: 1.25rem; line-height: 1.7;">
                <li>你应对自己账号下的所有操作负责，请妥善保管账号和密码。</li>
                <li>请遵守法律法规，不发布违法、违规、侵权或不适宜在校园传播的内容。</li>
                <li>请尊重他人，不得进行人身攻击、恶意骚扰、散布谣言等行为。</li>
                <li>除法律另有规定外，用户在本站发布的内容，版权归原作者所有，本站在合理范围内享有展示权。</li>
                <li>本站有权对违反规定的内容进行删除，对多次违规的账号进行限制或封禁。</li>
            </ol>
            <p style="color: var(--text-muted); margin-top: 0.75rem; font-size: 0.875rem;">
                说明：本页面为示例性用户协议文案，可根据实际需要在部署时补充或替换为正式版本。
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


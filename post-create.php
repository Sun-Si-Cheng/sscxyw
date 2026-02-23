<?php
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$categories = getCategories();
$selectedCategory = isset($_GET['category']) ? intval($_GET['category']) : 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = '无效请求，请刷新页面重试';
    } else {
        $categoryId = intval($_POST['category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $contentRaw = trim($_POST['content'] ?? '');
        $contentHtml = sanitizePostHtml($contentRaw);
        $contentText = stripHtmlToText($contentHtml);
        if (mb_strlen($contentText) > 5000) {
            $contentText = mb_substr($contentText, 0, 5000);
        }
        $content = $contentHtml; // 兼容旧字段

        // 验证输入
        if ($categoryId <= 0) {
            $error = '请选择帖子板块';
        } elseif (empty($title)) {
            $error = '请输入帖子标题';
        } elseif (mb_strlen($title) > 200) {
            $error = '标题长度不能超过200个字符';
        } elseif (empty($contentText)) {
            $error = '请输入帖子内容';
        } else {
            $category = getCategory($categoryId);
            if (!$category) {
                $error = '选择的板块不存在';
            } else {
                $pdo = getDBConnection();
                try {
                    $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, title, content, content_html, content_text) VALUES (?, ?, ?, ?, ?, ?)");
                    $ok = $stmt->execute([$_SESSION['user_id'], $categoryId, $title, $content, $contentHtml, $contentText]);
                    if (!$ok) {
                        // 尝试兼容旧表结构
                        $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, title, content) VALUES (?, ?, ?, ?)");
                        $ok = $stmt->execute([$_SESSION['user_id'], $categoryId, $title, $content]);
                    }
                    if ($ok) {
                        $postId = $pdo->lastInsertId();
                        redirect('post.php?id=' . $postId);
                    } else {
                        $error = '发布失败，请稍后重试';
                    }
                } catch (PDOException $e) {
                    if (ENVIRONMENT === 'development') {
                        $error = '发布失败: ' . $e->getMessage();
                    } else {
                        $error = '发布失败，请稍后重试';
                        error_log('发布帖子失败: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

$pageTitle = '发布帖子';
include __DIR__ . '/includes/header.php';
?>

<div class="post-form-container">
    <div class="post-form-box">
        <h2><i class="fas fa-plus-circle"></i> 发布帖子</h2>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        
        <form method="POST" action="" class="post-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-group">
                <label for="category_id">选择板块 <span class="required">*</span></label>
                <select name="category_id" id="category_id" required>
                    <option value="">请选择板块</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $selectedCategory == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo clean($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="title">标题 <span class="required">*</span></label>
                <input type="text" id="title" name="title" required 
                       placeholder="请输入帖子标题（最多200字）"
                       value="<?php echo isset($_POST['title']) ? clean($_POST['title']) : ''; ?>"
                       maxlength="200">
            </div>
            
            <div class="form-group">
                <label for="content">内容 <span class="required">*</span></label>
                <div id="editor-container" class="rich-editor"></div>
                <textarea id="content" name="content" rows="1" required style="display:none;"><?php echo isset($_POST['content']) ? clean($_POST['content']) : ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <a href="index.php" class="btn btn-outline">取消</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> 发布帖子
                </button>
            </div>
        </form>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: '请输入帖子内容...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }, 'bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }, 'blockquote'],
                ['link', 'image'],
                ['clean']
            ]
        }
    });
    var form = document.querySelector('.post-form');
    var contentField = document.getElementById('content');
    form.addEventListener('submit', function() {
        contentField.value = quill.root.innerHTML;
    });
    // 图片上传：点击图片按钮时使用自定义 handler
    var toolbar = quill.getModule('toolbar');
    toolbar.addHandler('image', function() {
        var input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
        input.onchange = function() {
            var file = input.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/upload_image.php');
            xhr.onload = function() {
                var res = JSON.parse(xhr.responseText);
                if (res.error) {
                    alert(res.error);
                    return;
                }
                var range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', res.url);
                quill.setSelection(range.index + 1);
            };
            xhr.send(fd);
        };
        input.click();
    });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

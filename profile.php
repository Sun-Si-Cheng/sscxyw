<?php
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = '无效请求，请刷新页面重试';
    } else {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $signature = trim($_POST['signature'] ?? '');
        
        // 验证输入
        if (empty($email)) {
            $error = '邮箱不能为空';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的邮箱地址';
        } else {
            $pdo = getDBConnection();
            
            // 检查邮箱是否已被其他用户使用
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $currentUser['id']]);
            if ($stmt->fetch()) {
                $error = '该邮箱已被其他用户使用';
            } else {
                // 处理头像上传
                $avatar = $currentUser['avatar'];
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['avatar'];
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    
                    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
                        $error = '只允许上传 JPG、PNG、GIF、WebP 格式的图片';
                    } elseif ($file['size'] > MAX_FILE_SIZE) {
                        $error = '图片大小不能超过 5MB';
                    } else {
                        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                        $ext = $extMap[$mime] ?? 'jpg';
                        $avatar = uniqid() . '.' . $ext;
                        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
                        $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $avatar;
                        
                        // 确保上传目录存在
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0755, true);
                        }
                        
                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            if ($currentUser['avatar'] && $currentUser['avatar'] !== 'default_avatar.png') {
                                $oldAvatarPath = $uploadDir . DIRECTORY_SEPARATOR . $currentUser['avatar'];
                                if (file_exists($oldAvatarPath)) {
                                    @unlink($oldAvatarPath);
                                }
                            }
                        } else {
                            $error = '头像上传失败';
                            $avatar = $currentUser['avatar'];
                        }
                    }
                }
                
                if (empty($error)) {
                    // 更新用户信息
                    $stmt = $pdo->prepare("UPDATE users SET nickname = ?, email = ?, signature = ?, avatar = ? WHERE id = ?");
                    if ($stmt->execute([$nickname, $email, $signature, $avatar, $currentUser['id']])) {
                        $success = '个人信息更新成功！';
                        // 刷新用户信息
                        $currentUser = getCurrentUser();
                    } else {
                        $error = '更新失败，请稍后重试';
                    }
                }
            }
        }
    }
}

$pageTitle = '个人中心';
include __DIR__ . '/includes/header.php';
?>

<div class="profile-container">
    <div class="profile-layout">
        <!-- 左侧：个人信息 -->
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar-wrapper">
                    <img src="<?php echo getAvatarUrl($currentUser['avatar']); ?>" alt="<?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?>" class="profile-avatar-lg">
                </div>
                <h3 class="profile-name"><?php echo clean($currentUser['nickname'] ?: $currentUser['username']); ?></h3>
                <p class="profile-username">@<?php echo clean($currentUser['username']); ?></p>
                <p class="profile-signature"><?php echo clean($currentUser['signature'] ?: '这个人很懒，什么都没写~'); ?></p>
                <div class="profile-meta">
                    <div class="meta-item">
                        <span class="meta-label">注册时间</span>
                        <span class="meta-value"><?php echo date('Y-m-d', strtotime($currentUser['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">角色</span>
                        <span class="meta-value"><?php echo $currentUser['role'] === 'admin' ? '管理员' : '普通用户'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 右侧：编辑表单 -->
        <div class="profile-main">
            <div class="profile-form-card">
                <h3><i class="fas fa-edit"></i> 编辑资料</h3>
                
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <?php echo showSuccess($success); ?>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label for="avatar">头像</label>
                        <div class="avatar-upload">
                            <img src="<?php echo getAvatarUrl($currentUser['avatar']); ?>" alt="当前头像" id="avatar-preview">
                            <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                            <label for="avatar" class="btn btn-outline btn-sm">
                                <i class="fas fa-camera"></i> 更换头像
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">用户名</label>
                            <input type="text" id="username" value="<?php echo clean($currentUser['username']); ?>" disabled>
                            <small>用户名不可修改</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="nickname">昵称</label>
                            <input type="text" id="nickname" name="nickname" 
                                   value="<?php echo clean($currentUser['nickname']); ?>"
                                   placeholder="设置一个昵称">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">邮箱 <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo clean($currentUser['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="signature">个性签名</label>
                        <textarea id="signature" name="signature" rows="3" 
                                  placeholder="写点什么介绍自己..."><?php echo clean($currentUser['signature']); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="change-password.php" class="btn btn-outline">修改密码</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存修改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

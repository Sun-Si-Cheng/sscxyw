<?php
/**
 * 校园论坛可视化安装程序
 */

// 定义安装锁文件路径
define('INSTALL_LOCK', __DIR__ . '/install.lock');

// 检查是否已安装
if (file_exists(INSTALL_LOCK)) {
    die('<h2>安装已完成</h2><p>如需重新安装，请先删除 install.lock 文件</p>');
}

// 步骤处理
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// 获取系统信息
function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'php_version_ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'gd' => extension_loaded('gd'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_execution_time' => ini_get('max_execution_time'),
    ];
}

// 检查目录权限
function checkPermissions() {
    $dirs = [
        'config' => is_writable(__DIR__ . '/config'),
        'uploads' => is_writable(__DIR__ . '/uploads'),
        'uploads/avatars' => is_writable(__DIR__ . '/uploads/avatars'),
        'uploads/posts' => is_writable(__DIR__ . '/uploads/posts'),
    ];
    return $dirs;
}

// 步骤处理
switch ($step) {
    case 1:
        // 环境检测
        $systemInfo = getSystemInfo();
        $permissions = checkPermissions();
        break;
        
    case 2:
        // 数据库配置
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dbHost = trim($_POST['db_host'] ?? 'localhost');
            $dbPort = trim($_POST['db_port'] ?? '3306');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';
            $dbPrefix = trim($_POST['db_prefix'] ?? '');
            
            // 验证输入
            if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
                $error = '请填写完整的数据库信息';
            } else {
                // 测试连接
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 保存配置到 session
                    session_start();
                    $_SESSION['install_db'] = [
                        'host' => $dbHost,
                        'port' => $dbPort,
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass,
                        'prefix' => $dbPrefix,
                    ];
                    
                    // 跳转到下一步
                    header('Location: install.php?step=3');
                    exit;
                } catch (PDOException $e) {
                    $error = '数据库连接失败：' . $e->getMessage();
                }
            }
        }
        break;
        
    case 3:
        // 管理员设置
        session_start();
        if (!isset($_SESSION['install_db'])) {
            header('Location: install.php?step=2');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siteName = trim($_POST['site_name'] ?? '校园论坛');
            $adminUser = trim($_POST['admin_user'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';
            $adminPass2 = $_POST['admin_pass2'] ?? '';
            $adminEmail = trim($_POST['admin_email'] ?? '');
            
            // 验证输入
            if (empty($siteName) || empty($adminUser) || empty($adminPass) || empty($adminEmail)) {
                $error = '请填写完整的管理员信息';
            } elseif (strlen($adminUser) < 3) {
                $error = '管理员用户名至少3个字符';
            } elseif (strlen($adminPass) < 6) {
                $error = '管理员密码至少6个字符';
            } elseif ($adminPass !== $adminPass2) {
                $error = '两次输入的密码不一致';
            } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $error = '请输入有效的邮箱地址';
            } else {
                // 保存管理员信息
                $_SESSION['install_admin'] = [
                    'site_name' => $siteName,
                    'username' => $adminUser,
                    'password' => $adminPass,
                    'email' => $adminEmail,
                ];
                
                // 跳转到下一步
                header('Location: install.php?step=4');
                exit;
            }
        }
        break;
        
    case 4:
        // 执行安装
        session_start();
        if (!isset($_SESSION['install_db']) || !isset($_SESSION['install_admin'])) {
            header('Location: install.php?step=2');
            exit;
        }
        
        $db = $_SESSION['install_db'];
        $admin = $_SESSION['install_admin'];
        
        try {
            // 连接数据库
            $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建数据库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db['name']}`");
            
            // 读取并执行 SQL 文件
            $sql = file_get_contents(__DIR__ . '/database_import.sql');
            
            // 替换表前缀
            if (!empty($db['prefix'])) {
                $sql = str_replace('CREATE TABLE IF NOT EXISTS ', 'CREATE TABLE IF NOT EXISTS `' . $db['prefix'], $sql);
                $sql = str_replace('INSERT INTO ', 'INSERT INTO `' . $db['prefix'], $sql);
                $sql = str_replace('REFERENCES ', 'REFERENCES `' . $db['prefix'], $sql);
                $sql = str_replace('`' . $db['prefix'] . '`', '`' . $db['prefix'], $sql);
            }
            
            // 分割并执行 SQL 语句
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // 更新管理员账号
            $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
            $usersTable = empty($db['prefix']) ? 'users' : $db['prefix'] . 'users';
            $stmt = $pdo->prepare("UPDATE `{$usersTable}` SET username = ?, password = ?, email = ?, nickname = ? WHERE id = 1");
            $stmt->execute([$admin['username'], $hashedPassword, $admin['email'], $admin['username']]);
            
            // 保存数据库配置
            $configContent = "<?php\n// 数据库配置文件\n\n";
            $configContent .= "define('DB_HOST', '{$db['host']}');\n";
            $configContent .= "define('DB_USER', '{$db['user']}');\n";
            $configContent .= "define('DB_PASS', '{$db['pass']}');\n";
            $configContent .= "define('DB_NAME', '{$db['name']}');\n";
            $configContent .= "define('DB_PREFIX', '{$db['prefix']}');\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
            $configContent .= file_get_contents(__DIR__ . '/config/database.php');
            
            // 提取 getDBConnection 函数部分
            preg_match('/\/\/ 创建数据库连接.*$/s', $configContent, $matches);
            if ($matches) {
                $configContent = "<?php\n// 数据库配置文件\n\n";
                $configContent .= "define('DB_HOST', '{$db['host']}');\n";
                $configContent .= "define('DB_USER', '{$db['user']}');\n";
                $configContent .= "define('DB_PASS', '{$db['pass']}');\n";
                $configContent .= "define('DB_NAME', '{$db['name']}');\n";
                $configContent .= "define('DB_PREFIX', '{$db['prefix']}');\n";
                $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
                $configContent .= $matches[0];
            }
            
            file_put_contents(__DIR__ . '/config/database.php', $configContent);
            
            // 更新网站名称
            $configFile = file_get_contents(__DIR__ . '/config/config.php');
            $configFile = str_replace("define('SITE_NAME', '校园论坛')", "define('SITE_NAME', '" . addslashes($admin['site_name']) . "')", $configFile);
            file_put_contents(__DIR__ . '/config/config.php', $configFile);
            
            // 创建安装锁文件
            file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'));
            
            // 清除 session
            session_destroy();
            
            $success = '安装成功！';
            
        } catch (Exception $e) {
            $error = '安装失败：' . $e->getMessage();
        }
        break;
        
    default:
        $step = 1;
        $systemInfo = getSystemInfo();
        $permissions = checkPermissions();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>校园论坛 - 安装向导</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .install-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        /* 步骤指示器 */
        .step-indicator {
            display: flex;
            justify-content: center;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            color: #28a745;
        }
        
        .step i {
            font-size: 16px;
        }
        
        .step-divider {
            width: 30px;
            height: 2px;
            background: #dee2e6;
            margin: 0 8px;
        }
        
        /* 内容区域 */
        .install-content {
            padding: 30px;
        }
        
        .content-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }
        
        /* 环境检测 */
        .check-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #dee2e6;
        }
        
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .check-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .check-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .check-item.success .check-icon {
            background: #28a745;
            color: white;
        }
        
        .check-item.error .check-icon {
            background: #dc3545;
            color: white;
        }
        
        .check-name {
            font-weight: 500;
            color: #333;
        }
        
        .check-value {
            font-size: 13px;
            color: #6c757d;
        }
        
        .check-status {
            font-size: 13px;
            font-weight: 500;
        }
        
        .check-item.success .check-status {
            color: #28a745;
        }
        
        .check-item.error .check-status {
            color: #dc3545;
        }
        
        /* 表单样式 */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group label .required {
            color: #dc3545;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* 提示消息 */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* 按钮 */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        /* 安装成功 */
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }
        
        .success-message {
            text-align: center;
        }
        
        .success-message h2 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .success-message p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-box h4 {
            color: #004085;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #004085;
            margin: 5px 0;
        }
        
        /* 加载动画 */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* 响应式 */
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .step-indicator {
                flex-wrap: wrap;
            }
            
            .step-divider {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="fas fa-graduation-cap"></i> 校园论坛安装向导</h1>
            <p>简单几步，快速搭建你的校园论坛</p>
        </div>
        
        <!-- 步骤指示器 -->
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                <i class="fas fa-<?php echo $step > 1 ? 'check' : 'server'; ?>"></i>
                <span>环境检测</span>
            </div>
            <div class="step-divider"></div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                <i class="fas fa-<?php echo $step > 2 ? 'check' : 'database'; ?>"></i>
                <span>数据库配置</span>
            </div>
            <div class="step-divider"></div>
            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                <i class="fas fa-<?php echo $step > 3 ? 'check' : 'user-cog'; ?>"></i>
                <span>管理员设置</span>
            </div>
            <div class="step-divider"></div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                <i class="fas fa-flag-checkered"></i>
                <span>完成安装</span>
            </div>
        </div>
        
        <div class="install-content">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php switch($step): case 1: ?>
                <!-- 步骤1：环境检测 -->
                <h2 class="content-title"><i class="fas fa-server"></i> 服务器环境检测</h2>
                
                <div class="check-list">
                    <div class="check-item <?php echo $systemInfo['php_version_ok'] ? 'success' : 'error'; ?>">
                        <div class="check-info">
                            <div class="check-icon">
                                <i class="fas fa-<?php echo $systemInfo['php_version_ok'] ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <div class="check-name">PHP 版本</div>
                                <div class="check-value"><?php echo $systemInfo['php_version']; ?> (需要 >= 7.4.0)</div>
                            </div>
                        </div>
                        <div class="check-status"><?php echo $systemInfo['php_version_ok'] ? '通过' : '不通过'; ?></div>
                    </div>
                    
                    <div class="check-item <?php echo $systemInfo['pdo'] ? 'success' : 'error'; ?>">
                        <div class="check-info">
                            <div class="check-icon">
                                <i class="fas fa-<?php echo $systemInfo['pdo'] ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <div class="check-name">PDO 扩展</div>
                                <div class="check-value">数据库连接必需</div>
                            </div>
                        </div>
                        <div class="check-status"><?php echo $systemInfo['pdo'] ? '已安装' : '未安装'; ?></div>
                    </div>
                    
                    <div class="check-item <?php echo $systemInfo['pdo_mysql'] ? 'success' : 'error'; ?>">
                        <div class="check-info">
                            <div class="check-icon">
                                <i class="fas fa-<?php echo $systemInfo['pdo_mysql'] ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <div class="check-name">PDO MySQL 扩展</div>
                                <div class="check-value">MySQL 连接必需</div>
                            </div>
                        </div>
                        <div class="check-status"><?php echo $systemInfo['pdo_mysql'] ? '已安装' : '未安装'; ?></div>
                    </div>
                    
                    <div class="check-item <?php echo $systemInfo['mbstring'] ? 'success' : 'error'; ?>">
                        <div class="check-info">
                            <div class="check-icon">
                                <i class="fas fa-<?php echo $systemInfo['mbstring'] ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <div class="check-name">Mbstring 扩展</div>
                                <div class="check-value">多字节字符串处理</div>
                            </div>
                        </div>
                        <div class="check-status"><?php echo $systemInfo['mbstring'] ? '已安装' : '未安装'; ?></div>
                    </div>
                    
                    <?php foreach ($permissions as $dir => $writable): ?>
                    <div class="check-item <?php echo $writable ? 'success' : 'error'; ?>">
                        <div class="check-info">
                            <div class="check-icon">
                                <i class="fas fa-<?php echo $writable ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div>
                                <div class="check-name">目录权限：<?php echo $dir; ?></div>
                                <div class="check-value">需要可写权限</div>
                            </div>
                        </div>
                        <div class="check-status"><?php echo $writable ? '可写' : '不可写'; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="btn-group">
                    <?php
                    $allOk = $systemInfo['php_version_ok'] && $systemInfo['pdo'] && $systemInfo['pdo_mysql'] && 
                             $systemInfo['mbstring'] && !in_array(false, $permissions, true);
                    ?>
                    <?php if ($allOk): ?>
                    <a href="install.php?step=2" class="btn btn-primary">
                        下一步 <i class="fas fa-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <button class="btn btn-primary" disabled>
                        请修复以上问题后继续
                    </button>
                    <?php endif; ?>
                </div>
                
            <?php break; case 2: ?>
                <!-- 步骤2：数据库配置 -->
                <h2 class="content-title"><i class="fas fa-database"></i> 数据库配置</h2>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>数据库主机 <span class="required">*</span></label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>端口</label>
                            <input type="text" name="db_port" value="3306">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库名 <span class="required">*</span></label>
                        <input type="text" name="db_name" placeholder="请输入数据库名称" required>
                        <div class="form-hint">如果不存在，安装程序会自动创建</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>数据库用户名 <span class="required">*</span></label>
                            <input type="text" name="db_user" placeholder="请输入用户名" required>
                        </div>
                        <div class="form-group">
                            <label>数据库密码</label>
                            <input type="password" name="db_pass" placeholder="请输入密码">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>数据表前缀</label>
                        <input type="text" name="db_prefix" placeholder="如：forum_（可选）">
                        <div class="form-hint">如果数据库中已有其他程序，建议设置前缀</div>
                    </div>
                    
                    <div class="btn-group">
                        <a href="install.php?step=1" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 上一步
                        </a>
                        <button type="submit" class="btn btn-primary">
                            下一步 <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
                
            <?php break; case 3: ?>
                <!-- 步骤3：管理员设置 -->
                <h2 class="content-title"><i class="fas fa-user-cog"></i> 管理员设置</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>网站名称 <span class="required">*</span></label>
                        <input type="text" name="site_name" value="校园论坛" required>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员用户名 <span class="required">*</span></label>
                        <input type="text" name="admin_user" placeholder="请输入管理员用户名" required>
                        <div class="form-hint">至少3个字符，建议使用字母、数字</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>管理员密码 <span class="required">*</span></label>
                            <input type="password" name="admin_pass" placeholder="请输入密码" required>
                            <div class="form-hint">至少6个字符</div>
                        </div>
                        <div class="form-group">
                            <label>确认密码 <span class="required">*</span></label>
                            <input type="password" name="admin_pass2" placeholder="请再次输入密码" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员邮箱 <span class="required">*</span></label>
                        <input type="email" name="admin_email" placeholder="请输入邮箱地址" required>
                    </div>
                    
                    <div class="btn-group">
                        <a href="install.php?step=2" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 上一步
                        </a>
                        <button type="submit" class="btn btn-primary">
                            开始安装 <i class="fas fa-rocket"></i>
                        </button>
                    </div>
                </form>
                
            <?php break; case 4: ?>
                <!-- 步骤4：安装完成 -->
                <?php if ($success): ?>
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2>安装成功！</h2>
                    <p>校园论坛已成功安装，现在可以开始使用了</p>
                    
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> 重要提示</h4>
                        <p><strong>为了安全起见，请立即删除 install.php 和 install.lock 文件！</strong></p>
                        <p>后台管理地址：/admin（开发中）</p>
                    </div>
                    
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> 访问首页
                        </a>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-sign-in-alt"></i> 登录后台
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="success-message">
                    <div class="success-icon" style="background: #dc3545;">
                        <i class="fas fa-times"></i>
                    </div>
                    <h2 style="color: #dc3545;">安装失败</h2>
                    <p>安装过程中出现错误，请检查错误信息后重试</p>
                    
                    <div class="btn-group">
                        <a href="install.php?step=3" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回上一步
                        </a>
                        <a href="install.php?step=4" class="btn btn-primary">
                            <i class="fas fa-redo"></i> 重新安装
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php break; endswitch; ?>
        </div>
    </div>
</body>
</html>

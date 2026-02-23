<?php
/**
 * 校园论坛可视化安装程序
 */

// 定义安装锁文件路径
define('INSTALL_LOCK', __DIR__ . DIRECTORY_SEPARATOR . 'install.lock');

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
        'config' => is_writable(__DIR__ . DIRECTORY_SEPARATOR . 'config'),
        'uploads' => is_writable(__DIR__ . DIRECTORY_SEPARATOR . 'uploads'),
        'uploads/avatars' => is_writable(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars'),
        'uploads/posts' => is_writable(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'posts'),
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
            } elseif (!empty($dbPrefix)) {
                // 目前代码中所有 SQL 都使用固定表名，为避免安装失败，暂不支持自定义前缀
                $error = '当前版本暂不支持自定义数据表前缀，请将表前缀留空后重试';
            } else {
                // 测试连接
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
            
            // 读取并执行基础表结构 SQL 文件
            $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'database_import.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception('数据库文件 database_import.sql 不存在');
            }
            $sql = file_get_contents($sqlFile);
            
            // 分割并执行 SQL 语句
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // 如存在扩展表结构文件，继续导入（站内信、通知等功能所需）
            $extensionFile = __DIR__ . DIRECTORY_SEPARATOR . 'database_schema_extension.sql';
            if (file_exists($extensionFile)) {
                $extSql = file_get_contents($extensionFile);
                // 去掉或替换固定的 USE 语句，避免与上方已选择的数据库冲突
                $extSql = preg_replace('/^USE\s+.+?;\s*/mi', '', $extSql);
                $extStatements = array_filter(array_map('trim', explode(';', $extSql)));
                foreach ($extStatements as $statement) {
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
            }
            
            // 更新管理员账号
            $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
            $usersTable = empty($db['prefix']) ? 'users' : $db['prefix'] . 'users';
            $stmt = $pdo->prepare("UPDATE `{$usersTable}` SET username = ?, password = ?, email = ?, nickname = ? WHERE id = 1");
            $stmt->execute([$admin['username'], $hashedPassword, $admin['email'], $admin['username']]);
            
            // 保存数据库配置
            $dbConfigFile = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
            $configContent = "<?php\n// 数据库配置文件\n\n";
            $configContent .= "define('DB_HOST', '{$db['host']}');\n";
            $configContent .= "define('DB_USER', '{$db['user']}');\n";
            $configContent .= "define('DB_PASS', '{$db['pass']}');\n";
            $configContent .= "define('DB_NAME', '{$db['name']}');\n";
            $configContent .= "define('DB_PREFIX', '{$db['prefix']}');\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
            
            // 添加 getDBConnection 函数
            $configContent .= "// 创建数据库连接\n";
            $configContent .= "function getDBConnection() {\n";
            $configContent .= "    static \$pdo = null;\n";
            $configContent .= "    \n";
            $configContent .= "    if (\$pdo === null) {\n";
            $configContent .= "        try {\n";
            $configContent .= "            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;\n";
            $configContent .= "            \$options = [\n";
            $configContent .= "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
            $configContent .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $configContent .= "                PDO::ATTR_EMULATE_PREPARES => false,\n";
            $configContent .= "            ];\n";
            $configContent .= "            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);\n";
            $configContent .= "        } catch (PDOException \$e) {\n";
            $configContent .= "            die(\"数据库连接失败: \" . \$e->getMessage());\n";
            $configContent .= "        }\n";
            $configContent .= "    }\n";
            $configContent .= "    \n";
            $configContent .= "    return \$pdo;\n";
            $configContent .= "}\n";
            
            file_put_contents($dbConfigFile, $configContent);
            
            // 更新网站名称
            $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            if (file_exists($configFile)) {
                $configContent = file_get_contents($configFile);
                $configContent = str_replace("define('SITE_NAME', '校园论坛')", "define('SITE_NAME', '" . addslashes($admin['site_name']) . "')", $configContent);
                file_put_contents($configFile, $configContent);
            }
            
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="install-page">
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

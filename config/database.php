<?php
// 数据库配置文件

define('DB_HOST', 'localhost');
define('DB_USER', 'xyw');
define('DB_PASS', 'n4CEpCYZ5jBBj5Kn');
define('DB_NAME', 'xyw');
define('DB_CHARSET', 'utf8mb4');

// 创建数据库连接
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (ENVIRONMENT === 'development') {
                throw new Exception("数据库连接失败: " . $e->getMessage());
            } else {
                // 记录错误到日志
                error_log("数据库连接失败: " . $e->getMessage());
                // 显示用户友好的错误信息
                if (!headers_sent()) {
                    header('HTTP/1.1 503 Service Unavailable');
                    header('Content-Type: text/html; charset=utf-8');
                }
                echo '<h1>服务暂时不可用</h1><p>系统正在维护中，请稍后再试。</p>';
                exit(1);
            }
        }
    }
    
    return $pdo;
}

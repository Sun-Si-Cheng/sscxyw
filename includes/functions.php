<?php
// 公共函数文件

require_once __DIR__ . '/../config/config.php';

// 安全过滤函数
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 获取用户头像URL
function getAvatarUrl($avatar, $default = 'default_avatar.png') {
    $avatarFile = $avatar ?: $default;
    $avatarPath = 'uploads/avatars/' . $avatarFile;
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $avatarFile;
    if ($avatar && file_exists($fullPath)) {
        return $avatarPath;
    }
    return 'uploads/avatars/' . $default;
}

// 检查用户是否登录
function isLoggedIn() {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    
    // 检查记住我令牌
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("SELECT u.id, u.username FROM users u
                               JOIN user_tokens ut ON u.id = ut.user_id
                               WHERE ut.token = ? AND ut.expires_at > NOW() AND u.status = 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
    }
    
    return false;
}

// 获取当前登录用户信息
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// 检查是否为管理员
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// 重定向函数
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// 显示错误消息
function showError($message) {
    return '<div class="alert alert-danger">' . clean($message) . '</div>';
}

// 显示成功消息
function showSuccess($message) {
    return '<div class="alert alert-success">' . clean($message) . '</div>';
}

// 生成CSRF令牌
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 格式化时间
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d', $time);
    }
}

// 截取文本
function truncateText($text, $length = 100) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

// 获取所有板块
function getCategories() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

// 获取板块信息
function getCategory($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND status = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// 获取帖子列表
function getPosts($categoryId = null, $page = 1, $perPage = POSTS_PER_PAGE) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT p.*, u.username, u.nickname, u.avatar, c.name as category_name, c.icon as category_icon,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 1) as comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN categories c ON p.category_id = c.id
            WHERE p.status = 1";
    
    $params = [];
    if ($categoryId) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    $sql .= " ORDER BY p.is_top DESC, p.created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// 获取帖子总数
function getPostsCount($categoryId = null) {
    $pdo = getDBConnection();
    $sql = "SELECT COUNT(*) FROM posts WHERE status = 1";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND category_id = ?";
        $params[] = $categoryId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// 获取单个帖子
function getPost($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.nickname, u.avatar, u.signature, c.name as category_name, c.icon as category_icon
                          FROM posts p
                          JOIN users u ON p.user_id = u.id
                          JOIN categories c ON p.category_id = c.id
                          WHERE p.id = ? AND p.status = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// 增加浏览量
function increaseViews($postId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$postId]);
}

// 获取评论列表
function getComments($postId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.nickname, u.avatar, u.signature
                          FROM comments c
                          JOIN users u ON c.user_id = u.id
                          WHERE c.post_id = ? AND c.status = 1 AND c.parent_id IS NULL
                          ORDER BY c.created_at ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

// 获取回复列表
function getReplies($commentId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.nickname, u.avatar
                          FROM comments c
                          JOIN users u ON c.user_id = u.id
                          WHERE c.parent_id = ? AND c.status = 1
                          ORDER BY c.created_at ASC");
    $stmt->execute([$commentId]);
    return $stmt->fetchAll();
}

// 富文本：将 HTML 转为纯文本（用于搜索摘要）
function stripHtmlToText($html) {
    if ($html === null || $html === '') return '';
    // 先解码 HTML 实体
    $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// 富文本：白名单过滤，防 XSS（允许的标签）
function sanitizePostHtml($html) {
    if ($html === null || $html === '') return '';
    $allowed = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><h1><h2><h3><h4><blockquote><pre><code><img><span><div>';
    $text = strip_tags($html, $allowed);
    // 只允许 img src 为相对路径 uploads/ 或 /
    $text = preg_replace_callback('/<img\s+([^>]*?)>/i', function ($m) {
        if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $m[1], $u)) {
            $src = $u[1];
            if (strpos($src, 'uploads/') !== 0 && strpos($src, '/uploads/') !== 0 && preg_match('/^https?:\/\//i', $src)) {
                return '';
            }
        }
        return $m[0];
    }, $text);
    return $text;
}

// 获取用户粉丝数、关注数
function getFollowCounts($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM follows WHERE following_id = ?) AS followers, (SELECT COUNT(*) FROM follows WHERE follower_id = ?) AS following");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetch();
}

// 当前用户是否已关注某用户
function isFollowing($followerId, $followingId) {
    if ($followerId == $followingId) return false;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$followerId, $followingId]);
    return (bool) $stmt->fetch();
}

// 根据 ID 获取用户（公开信息）
function getUserById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, nickname, avatar, signature, created_at FROM users WHERE id = ? AND status = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// 关注/取关
function followToggle($followerId, $followingId) {
    if ($followerId == $followingId) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$followerId, $followingId]);
    $exists = $stmt->fetch();
    if ($exists) {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);
        return false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$followerId, $followingId]);
        return true;
    }
}

// 获取粉丝列表 / 关注列表
function getFollowList($userId, $type = 'followers', $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;
    $perPage = (int)$perPage;
    $offset = (int)$offset;
    if ($type === 'followers') {
        $sql = "SELECT u.id, u.username, u.nickname, u.avatar FROM follows f JOIN users u ON u.id = f.follower_id WHERE f.following_id = ? AND u.status = 1 ORDER BY f.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
    } else {
        $sql = "SELECT u.id, u.username, u.nickname, u.avatar FROM follows f JOIN users u ON u.id = f.following_id WHERE f.follower_id = ? AND u.status = 1 ORDER BY f.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getFollowListCount($userId, $type = 'followers') {
    $pdo = getDBConnection();
    if ($type === 'followers') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows f JOIN users u ON u.id = f.follower_id WHERE f.following_id = ? AND u.status = 1");
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows f JOIN users u ON u.id = f.following_id WHERE f.follower_id = ? AND u.status = 1");
    }
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// 创建通知（供关注、消息等模块调用）
function createNotification($userId, $type, $data = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, data, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$userId, $type, $data !== null ? json_encode($data) : null]);
}

// 获取或创建两人私聊会话
function getOrCreatePrivateConversation($userId1, $userId2) {
    if ($userId1 == $userId2) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT c.id FROM conversations c
        INNER JOIN conversation_participants p1 ON p1.conversation_id = c.id AND p1.user_id = ?
        INNER JOIN conversation_participants p2 ON p2.conversation_id = c.id AND p2.user_id = ?
        WHERE c.type = 'private' LIMIT 1
    ");
    $stmt->execute([$userId1, $userId2]);
    $row = $stmt->fetch();
    if ($row) return (int) $row['id'];
    $pdo->exec("INSERT INTO conversations (type, created_at, updated_at) VALUES ('private', NOW(), NOW())");
    $convId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, joined_at) VALUES (?, ?, NOW()), (?, ?, NOW())");
    $stmt->execute([$convId, $userId1, $convId, $userId2]);
    return $convId;
}

// 当前用户参与的会话列表（含最新一条消息与未读数）
function getConversationsForUser($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT c.id, c.updated_at,
               (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_content,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_at,
               (SELECT sender_id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_sender_id
        FROM conversations c
        INNER JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$userId]);
    $list = $stmt->fetchAll();
    foreach ($list as &$row) {
        $other = getConversationOtherParticipant($row['id'], $userId);
        $row['other'] = $other;
        $row['unread'] = getConversationUnreadCount($row['id'], $userId);
    }
    return $list;
}

function getConversationOtherParticipant($conversationId, $currentUserId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.nickname, u.avatar FROM conversation_participants cp JOIN users u ON u.id = cp.user_id WHERE cp.conversation_id = ? AND cp.user_id != ?");
    $stmt->execute([$conversationId, $currentUserId]);
    return $stmt->fetch();
}

function getConversationUnreadCount($conversationId, $userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM message_receipts r
        INNER JOIN messages m ON m.id = r.message_id AND m.conversation_id = ? AND m.sender_id != ?
        WHERE r.user_id = ? AND r.is_read = 0
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    return (int) $stmt->fetchColumn();
}

// 会话历史消息（分页）
function getConversationMessages($conversationId, $userId, $page = 1, $perPage = 30) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;
    $perPage = (int)$perPage;
    $offset = (int)$offset;
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.content, m.content_type, m.created_at,
               u.username, u.nickname, u.avatar
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute([$conversationId]);
    return array_reverse($stmt->fetchAll());
}

function sendMessage($conversationId, $senderId, $content, $contentType = 'text') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, content_type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$conversationId, $senderId, $content, $contentType]);
    $messageId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT user_id FROM conversation_participants WHERE conversation_id = ? AND user_id != ?");
    $stmt->execute([$conversationId, $senderId]);
    while ($row = $stmt->fetch()) {
        $pdo->prepare("INSERT INTO message_receipts (message_id, user_id, is_read, created_at) VALUES (?, ?, 0, NOW())")->execute([$messageId, $row['user_id']]);
        createNotification($row['user_id'], 'new_message', ['conversation_id' => $conversationId, 'message_id' => $messageId]);
    }
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conversationId]);
    return $messageId;
}

function markConversationRead($conversationId, $userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE message_receipts r
        INNER JOIN messages m ON m.id = r.message_id AND m.conversation_id = ?
        SET r.is_read = 1, r.read_at = NOW()
        WHERE r.user_id = ? AND r.is_read = 0
    ");
    $stmt->execute([$conversationId, $userId]);
}

function getTotalUnreadMessagesCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM message_receipts WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// 通知列表（分页）
function getNotifications($userId, $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;
    $perPage = (int)$perPage;
    $offset = (int)$offset;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getNotificationsCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function admin_log($adminId, $action, $targetType = null, $targetId = null) {
    $pdo = getDBConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, ip, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adminId, $action, $targetType, $targetId, $ip]);
    } catch (Exception $e) {
        // table may not exist yet
    }
}

function getUnreadNotificationCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function markNotificationRead($notificationId, $userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
}

function markAllNotificationsRead($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
}

// 搜索帖子（全文）和用户（LIKE）
function searchPosts($keyword, $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;
    $perPage = (int)$perPage;
    $offset = (int)$offset;
    $kw = trim($keyword);
    if ($kw === '') return ['items' => [], 'total' => 0];
    $like = '%' . $kw . '%';
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.user_id, p.title, p.content_text, p.created_at, p.views,
                   u.username, u.nickname, u.avatar, c.name as category_name
            FROM posts p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.status = 1 AND (MATCH(p.title, p.content_text) AGAINST(? IN NATURAL LANGUAGE MODE) OR p.title LIKE ? OR p.content_text LIKE ?)
            ORDER BY p.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute([$kw, $like, $like]);
        $items = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE p.status = 1 AND (MATCH(p.title, p.content_text) AGAINST(? IN NATURAL LANGUAGE MODE) OR p.title LIKE ? OR p.content_text LIKE ?)");
        $stmt->execute([$kw, $like, $like]);
        $total = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.user_id, p.title, p.content_text, p.created_at, p.views,
                   u.username, u.nickname, u.avatar, c.name as category_name
            FROM posts p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.status = 1 AND (p.title LIKE ? OR COALESCE(p.content_text, p.content) LIKE ?)
            ORDER BY p.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute([$like, $like]);
        $items = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE p.status = 1 AND (p.title LIKE ? OR COALESCE(p.content_text, p.content) LIKE ?)");
        $stmt->execute([$like, $like]);
        $total = (int) $stmt->fetchColumn();
    }
    return ['items' => $items, 'total' => $total];
}

function searchUsers($keyword, $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;
    $perPage = (int)$perPage;
    $offset = (int)$offset;
    $kw = trim($keyword);
    if ($kw === '') return ['items' => [], 'total' => 0];
    $like = '%' . $kw . '%';
    $stmt = $pdo->prepare("
        SELECT id, username, nickname, avatar, signature, created_at
        FROM users
        WHERE status = 1 AND (username LIKE ? OR nickname LIKE ?)
        ORDER BY id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute([$like, $like]);
    $items = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 1 AND (username LIKE ? OR nickname LIKE ?)");
    $stmt->execute([$like, $like]);
    $total = (int) $stmt->fetchColumn();
    return ['items' => $items, 'total' => $total];
}

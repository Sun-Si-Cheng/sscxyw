-- 校园网核心功能扩展表结构（在现有 database.sql 基础上执行）
-- 执行前请确保已创建 campus_forum 数据库及 users, categories, posts, comments 表
-- 若 posts 表已存在 content_html/content_text 或 ft_posts_search 索引，请跳过对应 ALTER/ADD 语句

USE campus_forum;

-- 1. 扩展 posts 表：富文本与搜索（若列已存在可跳过对应语句）
ALTER TABLE posts ADD COLUMN content_html LONGTEXT NULL COMMENT '富文本HTML内容' AFTER content;
ALTER TABLE posts ADD COLUMN content_text TEXT NULL COMMENT '纯文本摘要用于全文搜索' AFTER content_html;

-- 将现有 content 同步到 content_html（首次迁移）
UPDATE posts SET content_html = content WHERE content_html IS NULL AND content IS NOT NULL;
UPDATE posts SET content_text = TRIM(REPLACE(REPLACE(REPLACE(content, '\r', ' '), '\n', ' '), '\t', ' ')) WHERE content_text IS NULL AND content IS NOT NULL;
UPDATE posts SET content_text = LEFT(content_text, 5000) WHERE content_text IS NOT NULL AND LENGTH(content_text) > 5000;

-- 全文索引（用于搜索；若已存在可跳过）
ALTER TABLE posts ADD FULLTEXT INDEX ft_posts_search (title, content_text);

-- 2. 帖子关联图片表
CREATE TABLE IF NOT EXISTS post_images (
  id INT PRIMARY KEY AUTO_INCREMENT,
  post_id INT NOT NULL COMMENT '帖子ID',
  path VARCHAR(500) NOT NULL COMMENT '存储路径',
  original_name VARCHAR(255) DEFAULT NULL COMMENT '原始文件名',
  size INT DEFAULT 0 COMMENT '文件大小(字节)',
  mime_type VARCHAR(100) DEFAULT NULL COMMENT 'MIME类型',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_post_images_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帖子图片';

-- 3. 用户关注关系
CREATE TABLE IF NOT EXISTS follows (
  id INT PRIMARY KEY AUTO_INCREMENT,
  follower_id INT NOT NULL COMMENT '关注者ID',
  following_id INT NOT NULL COMMENT '被关注者ID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_follow (follower_id, following_id),
  CHECK (follower_id != following_id),
  FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_follows_follower (follower_id),
  INDEX idx_follows_following (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户关注';

-- 4. 会话表（私信/群聊）
CREATE TABLE IF NOT EXISTS conversations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  type ENUM('private','group') DEFAULT 'private' COMMENT '会话类型',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_conversations_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会话';

-- 5. 会话参与者
CREATE TABLE IF NOT EXISTS conversation_participants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  conversation_id INT NOT NULL,
  user_id INT NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_conv_user (conversation_id, user_id),
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_cp_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会话参与者';

-- 6. 站内消息
CREATE TABLE IF NOT EXISTS messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  content TEXT NOT NULL COMMENT '消息内容',
  content_type VARCHAR(20) DEFAULT 'text' COMMENT 'text/image/rich',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_messages_conversation_created (conversation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站内消息';

-- 7. 消息已读回执
CREATE TABLE IF NOT EXISTS message_receipts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  message_id INT NOT NULL,
  user_id INT NOT NULL COMMENT '接收者(非发送者)',
  is_read TINYINT(1) DEFAULT 0,
  read_at TIMESTAMP NULL,
  UNIQUE KEY uk_message_user (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_receipts_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='消息已读';

-- 8. 通知表
CREATE TABLE IF NOT EXISTS notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL COMMENT '接收用户',
  type VARCHAR(50) NOT NULL COMMENT 'new_message/new_follower/system/post_reply等',
  data JSON DEFAULT NULL COMMENT '关联ID等扩展数据',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_read_created (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知';

-- 9. 后台操作日志
CREATE TABLE IF NOT EXISTS admin_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL COMMENT '操作类型',
  target_type VARCHAR(50) DEFAULT NULL COMMENT 'user/post/comment等',
  target_id INT DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_admin_logs_admin_created (admin_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台操作日志';

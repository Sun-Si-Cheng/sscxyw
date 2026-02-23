-- 校园论坛数据库设计
-- 创建数据库
CREATE DATABASE IF NOT EXISTS campus_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_forum;

-- 用户表
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码（加密存储）',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT '邮箱',
    nickname VARCHAR(50) COMMENT '昵称',
    avatar VARCHAR(255) DEFAULT 'default_avatar.png' COMMENT '头像',
    signature VARCHAR(255) COMMENT '个性签名',
    role ENUM('admin', 'moderator', 'user') DEFAULT 'user' COMMENT '角色',
    status TINYINT DEFAULT 1 COMMENT '状态：0-禁用，1-正常',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    last_login TIMESTAMP NULL COMMENT '最后登录时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 论坛板块表
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL COMMENT '板块名称',
    description TEXT COMMENT '板块描述',
    icon VARCHAR(50) COMMENT '图标',
    sort_order INT DEFAULT 0 COMMENT '排序',
    status TINYINT DEFAULT 1 COMMENT '状态：0-禁用，1-正常',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='论坛板块表';

-- 帖子表
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT '作者ID',
    category_id INT NOT NULL COMMENT '板块ID',
    title VARCHAR(200) NOT NULL COMMENT '标题',
    content TEXT NOT NULL COMMENT '内容',
    views INT DEFAULT 0 COMMENT '浏览次数',
    is_top TINYINT DEFAULT 0 COMMENT '是否置顶：0-否，1-是',
    is_essence TINYINT DEFAULT 0 COMMENT '是否精华：0-否，1-是',
    status TINYINT DEFAULT 1 COMMENT '状态：0-删除，1-正常',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帖子表';

-- 评论表
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL COMMENT '帖子ID',
    user_id INT NOT NULL COMMENT '评论者ID',
    parent_id INT DEFAULT NULL COMMENT '父评论ID（回复功能）',
    content TEXT NOT NULL COMMENT '评论内容',
    status TINYINT DEFAULT 1 COMMENT '状态：0-删除，1-正常',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论表';

-- 插入默认板块数据
INSERT INTO categories (name, description, icon, sort_order) VALUES
('校园公告', '学校重要通知和公告', 'bullhorn', 1),
('学习交流', '课程讨论、学习资料分享', 'book', 2),
('生活杂谈', '校园生活、日常分享', 'coffee', 3),
('二手交易', '闲置物品交换、买卖', 'shopping-cart', 4),
('失物招领', '寻物启事、失物认领', 'search', 5),
('社团活动', '社团招新、活动宣传', 'users', 6),
('求职招聘', '实习、兼职、就业信息', 'briefcase', 7),
('技术讨论', '编程、技术交流', 'code', 8);

-- 插入测试管理员账号（密码：admin123）
INSERT INTO users (username, password, email, nickname, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@campus.edu', '管理员', 'admin');

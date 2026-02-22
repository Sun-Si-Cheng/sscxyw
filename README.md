# 校园论坛系统

一个基于 PHP + MySQL 开发的校园论坛系统，为校园师生提供交流分享平台。

## 功能特性

### 用户系统
- 用户注册/登录/登出
- 个人资料管理（头像、昵称、签名）
- 密码修改
- 用户角色管理（管理员、普通用户）

### 论坛功能
- 多板块分类（校园公告、学习交流、生活杂谈等）
- 帖子发布、编辑、删除
- 帖子置顶、精华标记
- 评论和回复功能
- 浏览量统计

### 管理功能
- 帖子管理
- 评论管理
- 用户管理

## 技术栈

- **后端**: PHP 7.4+
- **数据库**: MySQL 5.7+
- **前端**: HTML5, CSS3, JavaScript
- **样式**: 自定义 CSS + Font Awesome 图标

## 安装部署

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx Web 服务器
- PDO PHP 扩展

### 安装步骤

1. **克隆项目到本地**
```bash
cd 校园网
```

2. **创建数据库**
- 登录 MySQL
- 创建数据库 `campus_forum`
- 导入 `database.sql` 文件

```bash
mysql -u root -p
CREATE DATABASE campus_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_forum;
SOURCE database.sql;
```

3. **配置数据库连接**
编辑 `config/database.php` 文件，修改数据库连接信息：
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // 你的数据库用户名
define('DB_PASS', '');            // 你的数据库密码
define('DB_NAME', 'campus_forum');
```

4. **配置网站**
- 将项目部署到 Web 服务器
- 确保 `uploads/avatars` 和 `uploads/posts` 目录可写

5. **访问网站**
打开浏览器访问：`http://localhost/校园网`

### 默认管理员账号
- 用户名: `admin`
- 密码: `admin123`

**注意**: 请在生产环境中修改默认管理员密码！

## 目录结构

```
校园网/
├── config/                 # 配置文件
│   ├── config.php         # 全局配置
│   └── database.php       # 数据库配置
├── includes/              # 公共文件
│   ├── functions.php      # 公共函数
│   ├── header.php         # 页面头部
│   └── footer.php         # 页面底部
├── assets/                # 静态资源
│   ├── css/              # 样式文件
│   │   └── style.css
│   ├── js/               # JavaScript 文件
│   │   └── main.js
│   └── images/           # 图片资源
├── uploads/              # 上传文件
│   ├── avatars/         # 用户头像
│   └── posts/           # 帖子图片
├── index.php            # 首页
├── login.php            # 登录页
├── register.php         # 注册页
├── logout.php           # 登出处理
├── profile.php          # 个人中心
├── change-password.php  # 修改密码
├── my-posts.php         # 我的帖子
├── category.php         # 板块页面
├── post.php             # 帖子详情
├── post-create.php      # 发布帖子
├── post-edit.php        # 编辑帖子
├── post-delete.php      # 删除帖子
├── comment-delete.php   # 删除评论
└── database.sql         # 数据库结构
```

## 安全特性

- 密码使用 bcrypt 加密存储
- SQL 注入防护（PDO 预处理语句）
- XSS 攻击防护（HTML 实体编码）
- CSRF 防护（令牌验证）
- 文件上传类型和大小限制
- 用户权限验证

## 自定义配置

### 修改网站名称
编辑 `config/config.php`：
```php
define('SITE_NAME', '你的论坛名称');
define('SITE_DESCRIPTION', '你的论坛描述');
```

### 修改分页数量
编辑 `config/config.php`：
```php
define('POSTS_PER_PAGE', 10);     # 每页帖子数
define('COMMENTS_PER_PAGE', 20);  # 每页评论数
```

### 修改上传限制
编辑 `config/config.php`：
```php
define('MAX_FILE_SIZE', 5 * 1024 * 1024);  # 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
```

## 浏览器支持

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## 开发计划

- [ ] 富文本编辑器
- [ ] 图片上传功能
- [ ] 站内消息系统
- [ ] 用户关注功能
- [ ] 消息通知
- [ ] 搜索功能
- [ ] 后台管理系统

## 许可证

MIT License

## 联系方式

如有问题或建议，欢迎提交 Issue 或联系管理员。

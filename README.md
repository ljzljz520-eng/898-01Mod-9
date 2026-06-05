# 学习交流论坛

一个基于 Laravel Blade + Tailwind CSS 的学习交流论坛系统，支持用户注册登录、主题发布、回复讨论等功能

> **⚠️ 使用前必读**：请等待容器启动成功、依赖安装及数据初始化完成后，再打开网页访问；否则可能无法正常打开页面。

## 🛠 技术栈

- **Frontend**: Laravel Blade + Tailwind CSS + Vite
- **Backend**: Laravel 10.x + PHP 8.2
- **Database**: MySQL 8.0
- **部署**: Docker Compose


## 🚀 启动指南

### 前置要求

- Docker Desktop 已安装并运行
- 确保端口 8000、3306 未被占用

### 启动步骤

1. 进入项目目录：
```bash
cd forum
```

2. 启动所有服务：
```bash
docker compose up --build
```

3. **等待容器启动完成**（首次启动会自动安装依赖、运行迁移和填充数据），确认无报错后再进行下一步。

4. 访问服务：
   - **前台用户界面**: http://localhost:8000
   - **后端 API**: http://localhost:8000/api

## 🔗 服务地址

- **Frontend**: http://localhost:8000
- **Backend API**: http://localhost:8000/api
- **Database**: localhost:3306

## 🧪 测试账号

### 普通用户账号
- **用户名**: user1
- **邮箱**: user1@forum.com
- **密码**: 123456

- **用户名**: user2
- **邮箱**: user2@forum.com
- **密码**: 123456

## 📝 功能特性

- ✅ 用户注册/登录（Laravel Session 认证）
- ✅ 注册成功后跳转登录页（不自动登录）
- ✅ 主题发布/编辑/删除（仅作者本人可编辑/删除）
- ✅ 回复功能（仅本人可删除）
- ✅ 主题列表（分页、搜索、分类筛选）
- ✅ 分类/关键词搜索/分页无刷新更新
- ✅ 成功/错误提示 3 秒自动消失（非原生弹窗）
- ✅ 底部版权区固定在页面底部
- ✅ 初始化示例数据：主题标题/内容/评论无重复，方便演示
- ✅ 响应式设计（适配手机、平板、PC）

## 🐳 Docker 服务说明

- **mysql**: MySQL 8.0 数据库服务
- **laravel-api**: Laravel 后端服务（包含前端 Blade 视图）

## ✅ 关键约定（避免踩坑）

- **认证方式**：使用 Laravel Session 认证（前台表单登录）
- **注册流程**：注册成功后**跳转登录页**，不会自动写入登录态
- **软删除策略**：
  - `topics`、`replies` 使用软删除（`deleted_at`）
- **初始化脚本**：
  - 容器启动时自动建表、插入演示数据
  - 多次启动不会重复插入相同主题/评论

## 📦 项目结构

```
forum/
├── backend/          # Laravel 后端（包含 Blade 视图）
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/     # Web 控制器
│   │   │   └── Controllers/Api/ # API 控制器
│   │   └── Models/
│   ├── resources/
│   │   ├── views/     # Blade 模板
│   │   ├── css/       # Tailwind CSS
│   │   └── js/        # JavaScript
│   └── routes/
│       ├── web.php    # Web 路由
│       └── api.php    # API 路由
├── nginx/             # Nginx 配置
└── docker-compose.yml
```

## 🔧 开发说明

### 后端开发

进入后端容器：
```bash
docker exec -it forum_laravel bash
```

运行 Artisan 命令：
```bash
php artisan migrate
php artisan db:seed
```

### 前端开发

前端资源修改后需要重新构建：
```bash
docker exec -it forum_laravel bash
cd /var/www/html
npm run build
```

或者重启容器以触发自动构建：
```bash
docker compose restart laravel-api
```

## 📄 许可证

MIT

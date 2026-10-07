# CLAUDE.md

本文件为 Claude Code (claude.ai/code) 在本仓库中工作时提供指导。

## 概述

一个基于 **webman**（基于 Workerman 的常驻内存 PHP HTTP 框架，v2.1，PHP >= 8.1）的测速摄像头（"Blitzer"）告警后端。它对外为移动 App 提供 REST API（邮箱认证、设备绑定、基于地理位置的摄像头/事件查询），并提供管理后台，以及一条从外部数据源（blitzer.de JSON、SCDB XML）同步摄像头/事件数据到 MySQL 的流水线。

Workerman **本身即为** HTTP 服务器 —— 没有 Nginx/Apache。它监听 `http://0.0.0.0:9011`（`config/process.php`）。

## 命令

```bash
composer install              # 安装依赖

php start.php start           # 前台运行
php start.php start -d        # 以守护进程运行（仅 Linux/macOS —— 在 Windows 上不可用）
php start.php stop
php start.php restart
php start.php reload          # 平滑重载（生产环境首选）
php start.php status
```

没有测试套件（缺少 `/tests` 目录，也没有 phpunit），并且未配置 linter/formatter。

## 架构

### 入口与配置
- 入口 `start.php` → `support\App::run()`。`support/bootstrap.php` 加载 `.env`（若存在）、配置、中间件、bootstraps 和路由。
- 配置位于 `config/`，通过 `config('file.key')` 和 `config('plugin.<firm>.<name>.key')` 访问。
- `config/process.php` 定义了常驻进程：`webman`（HTTP，处理器为 `app\process\Http`，它继承 `Webman\App`）和 `monitor`（仅开发环境使用的文件/内存热重载，在 Windows 上禁用）。

### 路由
- `config/route.php` 为空 → 约定式路由 `/{controller}/{action}` → `app\controller\{Name}Controller::{action}`（`controller_suffix` = `Controller`，`controller_reuse` = false）。
- 插件通过各自的 `plugin/*/config/route.php` 贡献路由。

### 插件（`plugin/`）
- `plugin/admin/` —— `webman/admin` 后台（Layui UI），挂载于 `/app/admin`；带有自己的 controllers/models/views，并扩展了自定义控制器（Device、DeviceEvent、Feedback2、Regulations、Message、Role、Rule 等）。
- `plugin/email/` —— 基于 PHPMailer 的邮件插件；SMTP 配置和邮件模板存储在 `wa_option` 表中（`email_setting`、`email_template_*`）。

### 认证（全局中间件）
- `app\middleware\AccessControl`（注册于 `config/middleware.php`）对每个请求执行 token 认证。
- 控制器通过 `protected $noNeedLogin = [...]` 声明免登录项。`['*']` = 整个控制器公开；否则列出需要跳过的 action 名称。已认证的 action 会读取 `token` 请求头，从 `wa_users` 中查找用户，并把 `user_id` + `user_info` 注入 `$request->get()`。
- `app\model\Rsa` 负责 RSA（JSEncrypt / PKCS1 v1.5）密码加密；密钥位于 `config/certs/`。

### 数据层
- ORM 使用的是 **ThinkPHP ORM**（`webman/think-orm`），而非 Eloquent。查询请使用 `support\think\Db::name('table')` / `Db::table('t')`，模型基类为 `support\think\Model`。配置：`config/think-orm.php`（MySQL 库 `online`，表前缀 `wa_`）。
  - `config/database.php` 是未使用的 illuminate 风格遗留文件 —— 实际生效的 ORM 配置在 `config/think-orm.php`（bootstrap 加载 `Webman\ThinkOrm\ThinkOrm`）。
- Redis 通过 `webman/redis-queue` 使用；连接配置在 `config/plugin/webman/redis-queue/redis.php`。默认缓存为文件缓存（`runtime/cache`）。
- 辅助函数 `support\Redis::db_insert($message)` 向 `db_insert` 队列投递消息。

### 队列与后台任务
- 队列消费者位于 `app/queue/redis/`（每个都实现 `Webman\RedisQueue\Consumer`，并声明 `$queue`/`$connection`）；共 8 个消费者进程（`config/plugin/webman/redis-queue/process.php`）。
  - `DbInsert`（队列 `db_insert`）批量插入数据行到 `device_new` 暂存表。
  - `EventInsert`（队列 `event_insert`）插入数据到 `wa_device_event`。
- `app/process/` 中的长时同步任务（`SyncBlitzer`、`XmlBlitzer`）通过 HTTP 触发（`SyncController::jsonStart`/`xmlStart`/等），依托 `Workerman\Timer` 运行，而**非**守护进程（它们在 `config/process.php` 中的条目已被注释掉）。

### 数据同步流水线（摄像头与事件）
- `SyncBlitzer`：下载 `direct.blitzer.de/export/v3/json/` → `runtime/blitzer_full.json` → 解析 → 暂存到 `blitzer_new`（`wa_device_event` 的副本）→ 原子 `RENAME TABLE` 交换。
- `XmlBlitzer`：下载 SCDB XML → `runtime/device.xml` → 用 `XMLReader` 解析 → 批量推送至 `db_insert` 队列 → 消费者写入 `device_new` → 原子交换进 `wa_device`（`SyncController::xmlChange`）。
- 核心模式是**暂存表 + 原子 `RENAME TABLE` 交换**，以实现零停机刷新；同步期间绝不原地更新 `wa_device`/`wa_device_event`。

### API 接口（`app/controller`）
- `IndexController` —— 移动端主 API：邮箱注册/登录/找回密码（验证码通过 `app\model\Email` 发送）、设备（`wa_equipment`）绑定、地址，以及核心的 `devices()`/`events()` 接口（使用 MySQL `ST_Distance_Sphere` 和 `app\model\Location` 进行方向/路线过滤）。App 的 i18n 是控制器中硬编码的 `$i18n` 映射（通过 `t()` 提供 en/de）。
- `ApiController` / `SyncController` —— 转发摄像头上报/更新到 blitzer.de，并触发手动同步。

### 地理匹配（`app/model/Location.php`）
- 纯 PHP 地理计算（不是 DB Model）：haversine（半正矢公式）、方位角、偏航距（cross-track distance），以及路线感知过滤（`filterCamerasInFront`、`filterCamerasBySide`）。`dirtype` 语义：0 = 单向，1 = 双向，2 = 全向。

## 约定与注意事项
- 常驻进程：代码每个 worker 只加载一次；修改后需执行 `php start.php reload`（或在 Linux 上由 `monitor` 进程自动重载）。避免使用会在请求之间泄漏的全局变量/静态状态。
- `config/app.php` 中设置了 `debug => true`，并保存了外部 blitzer.de 的 `export_key`；DB/Redis 凭据硬编码在 `config/think-orm.php` 和 `config/plugin/webman/redis-queue/redis.php` 中（未使用 `.env`）。
- 日志（Monolog）输出到 `runtime/logs/`。

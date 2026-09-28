# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A speed-camera ("Blitzer") alert backend built on **webman** (Workerman-based, long-running PHP HTTP framework, v2.1, PHP >= 8.1). It exposes a REST API for a mobile app (email auth, device binding, geolocated camera/event queries), an admin panel, and a pipeline that syncs camera/event data from external sources (blitzer.de JSON, SCDB XML) into MySQL.

Workerman **is** the HTTP server — no Nginx/Apache. It listens on `http://0.0.0.0:9011` (`config/process.php`).

## Commands

```bash
composer install              # install dependencies

php start.php start           # run in foreground
php start.php start -d        # daemonize (Linux/macOS only — NOT on Windows)
php start.php stop
php start.php restart
php start.php reload          # graceful reload (preferred in production)
php start.php status
```

No test suite (`/tests` absent, no phpunit) and no linter/formatter are configured.

## Architecture

### Entry & config
- Entry `start.php` → `support\App::run()`. `support/bootstrap.php` loads `.env` (if present), config, middleware, bootstraps, and routes.
- Config lives in `config/`, accessed via `config('file.key')` and `config('plugin.<firm>.<name>.key')`.
- `config/process.php` defines long-running processes: `webman` (HTTP, handler `app\process\Http` which extends `Webman\App`) and `monitor` (dev-only file/memory reload, disabled on Windows).

### Routing
- `config/route.php` is empty → convention routing `/{controller}/{action}` → `app\controller\{Name}Controller::{action}` (`controller_suffix` = `Controller`, `controller_reuse` = false).
- Plugins contribute their own routes via `plugin/*/config/route.php`.

### Plugins (`plugin/`)
- `plugin/admin/` — the `webman/admin` backend (Layui UI) served at `/app/admin`; has its own controllers/models/views and is extended with custom controllers (Device, DeviceEvent, Feedback2, Regulations, Message, Role, Rule, etc.).
- `plugin/email/` — PHPMailer-based mail plugin; SMTP config and email templates are stored in the `wa_option` table (`email_setting`, `email_template_*`).

### Auth (global middleware)
- `app\middleware\AccessControl` (registered in `config/middleware.php`) performs token auth for every request.
- Controllers declare `protected $noNeedLogin = [...]`. `['*']` = whole controller public; otherwise list action names to skip. Authed actions read the `token` header, look up the user in `wa_users`, and inject `user_id` + `user_info` into `$request->get()`.
- `app\model\Rsa` handles RSA (JSEncrypt / PKCS1 v1.5) password encryption; keys in `config/certs/`.

### Data layer
- ORM is **ThinkPHP ORM** (`webman/think-orm`), not Eloquent. Use `support\think\Db::name('table')` / `Db::table('t')` for queries and `support\think\Model` as the model base. Config: `config/think-orm.php` (MySQL db `online`, table prefix `wa_`).
  - `config/database.php` is an unused illuminate-style leftover — the active ORM is configured in `config/think-orm.php` (bootstrap loads `Webman\ThinkOrm\ThinkOrm`).
- Redis via `webman/redis-queue`; connection in `config/plugin/webman/redis-queue/redis.php`. Default cache is file (`runtime/cache`).
- Helper `support\Redis::db_insert($message)` enqueues to the `db_insert` queue.

### Queue & background work
- Queue consumers live in `app/queue/redis/` (each implements `Webman\RedisQueue\Consumer` with `$queue`/`$connection`); 8 consumer processes (`config/plugin/webman/redis-queue/process.php`).
  - `DbInsert` (queue `db_insert`) bulk-inserts rows into the `device_new` staging table.
  - `EventInsert` (queue `event_insert`) inserts into `wa_device_event`.
- Long sync jobs in `app/process/` (`SyncBlitzer`, `XmlBlitzer`) are triggered over HTTP (`SyncController::jsonStart`/`xmlStart`/etc.) via `Workerman\Timer`, not as daemon processes (their `config/process.php` entries are commented out).

### Data sync pipeline (cameras & events)
- `SyncBlitzer`: download `direct.blitzer.de/export/v3/json/` → `runtime/blitzer_full.json` → parse → stage in `blitzer_new` (copy of `wa_device_event`) → atomic `RENAME TABLE` swap.
- `XmlBlitzer`: download SCDB XML → `runtime/device.xml` → parse with `XMLReader` → push batches to the `db_insert` queue → consumer writes `device_new` → atomic swap into `wa_device` (`SyncController::xmlChange`).
- Core pattern is **staging table + atomic `RENAME TABLE` swap** for zero-downtime refresh; never update `wa_device`/`wa_device_event` in place during sync.

### API surface (`app/controller`)
- `IndexController` — main mobile API: email signup/login/forget (verification codes via `app\model\Email`), device (`wa_equipment`) binding, addresses, and the core `devices()`/`events()` endpoints (MySQL `ST_Distance_Sphere` + `app\model\Location` for direction/route filtering). App i18n is a hardcoded `$i18n` map in the controller (en/de via `t()`).
- `ApiController` / `SyncController` — forward camera reports/updates to blitzer.de and trigger manual syncs.

### Geo matching (`app/model/Location.php`)
- Pure-PHP geo math (not a DB Model): haversine, bearing, cross-track distance, and route-aware filtering (`filterCamerasInFront`, `filterCamerasBySide`). `dirtype` semantics: 0 = one-way, 1 = two-way, 2 = omnidirectional.

## Conventions & gotchas
- Long-running process: code loads once per worker; `php start.php reload` after changes (or the `monitor` process auto-reloads on Linux). Avoid globals/static state that leaks between requests.
- `config/app.php` sets `debug => true` and holds the external blitzer.de `export_key`; DB/Redis credentials are hardcoded in `config/think-orm.php` and `config/plugin/webman/redis-queue/redis.php` (no `.env` in use).
- Logs (Monolog) go to `runtime/logs/`.

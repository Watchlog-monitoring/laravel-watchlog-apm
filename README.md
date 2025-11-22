# Laravel APM – Watchlog Integration

🎯 Lightweight, production-ready Application Performance Monitoring (APM) middleware for Laravel apps, designed for [Watchlog](https://watchlog.io).

Tracks request duration, memory usage, and status codes — stores metrics in a buffer file, and automatically flushes them to your Watchlog Agent every 10 seconds.

---

## 🚀 Features

- ✅ Compatible with Laravel 10 & 12+
- 🧠 Smart file-based metric buffering (no database or queue needed)
- 🔁 Aggregates metrics per route (e.g. `/users/{id}`)
- 📊 Tracks duration, memory, and status codes
- 🔒 Safe and non-blocking — doesn't slow down your requests
- 🌐 Sends data automatically every 10 seconds to your Watchlog Agent
- 🏷️ Auto-detects service name from `.env` (`WATCHLOG_APM_SERVICE` or fallback to `APP_NAME`)

---

## 📦 Installation

### 1. Link local package (for development)

```bash
composer config repositories.watchlog-apm path ../laravel-watchlog-apm
composer require "watchlog/laravel-apm:*"
```

> Adjust the path to match your local project structure.

---

## ⚙️ Usage (Laravel 12+)

In your `bootstrap/app.php`, register the middleware:

```php
use Watchlog\LaravelAPM\Middleware\WatchlogAPM;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(...)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(WatchlogAPM::class);
    })
    ->create();
```

---

## 🛠️ Configuration

### Service Identification

You can define your service name explicitly in `.env`:

```env
WATCHLOG_APM_SERVICE=orders-api
```

If not set, it will fallback to:

```env
APP_NAME=laravel-app
```

### Agent URL Configuration

The package automatically detects the agent endpoint, but you can override it for Docker:

**Option 1: Environment Variable (Recommended for Docker)**

```env
WATCHLOG_APM_AGENT_URL=http://watchlog-agent:3774/apm
```

**Option 2: Config File**

Publish the config file (if available) or add to your `config/services.php`:

```php
'watchlog' => [
    'apm' => [
        'agent_url' => env('WATCHLOG_APM_AGENT_URL', ''),
    ],
],
```

**Option 3: Direct Usage**

```php
use Watchlog\LaravelAPM\Sender;

$sender = new Sender('http://watchlog-agent:3774/apm');
$sender->flush();
```

If not provided, the package will auto-detect:
- **Local / non-K8s**: `http://127.0.0.1:3774/apm`
- **Kubernetes**: `http://watchlog-node-agent.monitoring.svc.cluster.local:3774/apm`

---

## 📤 How it works

### During each request:
- The middleware tracks route, status code, duration, and memory.
- The data is written to a file: `storage/logs/apm-buffer.json`

### After the request ends:
- A shutdown function checks if 10 seconds have passed since the last flush.
- If yes, it:
  - Reads all pending metrics from `apm-buffer.json`
  - Aggregates them by route/method
  - Sends them to your Watchlog Agent as a JSON payload
  - Clears the buffer

---

## 📦 What gets sent?

```json
{
  "collected_at": "2025-05-18T12:00:00Z",
  "platformName": "laravel",
  "metrics": [
    {
      "type": "aggregated_request",
      "service": "orders-api",
      "path": "hello/{id}",
      "method": "GET",
      "request_count": 3,
      "error_count": 0,
      "avg_duration": 6.1,
      "max_duration": 8.2,
      "avg_memory": {
        "rss": 18432000,
        "heapUsed": 23998464,
        "heapTotal": 25600000
      }
    }
  ]
}
```

---

## 📁 File structure

```txt
laravel-watchlog-apm/
├── src/
│   ├── Middleware/
│   │   └── WatchlogAPM.php
│   ├── Collector.php
│   └── Sender.php
├── composer.json
├── README.md
```

---

## 🧪 Debugging & Testing

You can manually flush metrics via tinker:

```bash
php artisan tinker
>>> (new \Watchlog\LaravelAPM\Sender())->flush();
```

---

## 📁 Recommended `.gitignore`

Add the following to your Laravel app's `.gitignore`:

```gitignore
/storage/logs/apm-buffer.json
/storage/logs/apm-debug.log
/storage/framework/cache/watchlog-apm.lock
```

---

## 🐳 Docker Setup

When running your Laravel app in Docker, configure the agent URL:

**Docker Compose Example:**
```yaml
version: '3.8'

services:
  watchlog-agent:
    image: watchlog/agent:latest
    container_name: watchlog-agent
    ports:
      - "3774:3774"
    environment:
      - WATCHLOG_APIKEY=your-api-key
      - WATCHLOG_SERVER=https://log.watchlog.ir
    networks:
      - app-network

  laravel-app:
    build: .
    container_name: laravel-app
    ports:
      - "8000:8000"
    environment:
      - WATCHLOG_APM_AGENT_URL=http://watchlog-agent:3774/apm
      - WATCHLOG_APM_SERVICE=my-laravel-app
    depends_on:
      - watchlog-agent
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

**Docker Run Example:**
```bash
# 1. Create network
docker network create app-network

# 2. Run Watchlog Agent
docker run -d \
  --name watchlog-agent \
  --network app-network \
  -p 3774:3774 \
  -e WATCHLOG_APIKEY="your-api-key" \
  -e WATCHLOG_SERVER="https://log.watchlog.ir" \
  watchlog/agent:latest

# 3. Run Laravel app (set WATCHLOG_APM_AGENT_URL in your .env or docker run)
docker run -d \
  --name laravel-app \
  --network app-network \
  -p 8000:8000 \
  -e WATCHLOG_APM_AGENT_URL="http://watchlog-agent:3774/apm" \
  my-laravel-app
```

**Important Notes:**
- When using Docker, use the container name as the hostname (e.g., `watchlog-agent`)
- Both containers must be on the same Docker network
- The agent must be running before your app starts
- Set `WATCHLOG_APM_AGENT_URL` in your `.env` file or as an environment variable
- If `WATCHLOG_APM_AGENT_URL` is not provided, auto-detection will be used (local or Kubernetes)

---

## ✅ Notes

- The route path is captured via `$request->route()?->uri()` for correct dynamic segment grouping (`/users/{id}`)
- Multiple requests within 10 seconds are buffered, then aggregated and sent in a single payload
- The flush lock uses a simple file timestamp (`watchlog-apm.lock`) to prevent oversending
- The middleware is safe: flush failures won't crash your app

---

## 📝 License

MIT © Mohammadreza  
Built for [Watchlog.io](https://watchlog.io)

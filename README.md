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

## 🛠️ Service Identification

You can define your service name explicitly in `.env`:

```env
WATCHLOG_APM_SERVICE=orders-api
```

If not set, it will fallback to:

```env
APP_NAME=laravel-app
```

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

## ✅ Notes

- The route path is captured via `$request->route()?->uri()` for correct dynamic segment grouping (`/users/{id}`)
- Multiple requests within 10 seconds are buffered, then aggregated and sent in a single payload
- The flush lock uses a simple file timestamp (`watchlog-apm.lock`) to prevent oversending
- The middleware is safe: flush failures won't crash your app

---

## 📝 License

MIT © Mohammadreza  
Built for [Watchlog.io](https://watchlog.io)

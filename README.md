# Laravel APM – Watchlog Integration

🎯 Lightweight, aggregated Application Performance Monitoring (APM) middleware for Laravel 12+, designed to integrate with [Watchlog](https://watchlog.io).

Tracks request performance, aggregates metrics by route and method, and sends them to your Watchlog agent every 10 seconds — without slowing down your app.

---

## 🚀 Features

- 🔧 Compatible with Laravel 12 and the new middleware registration system
- 📊 Aggregates metrics by path and method
- ⚙️ Records average & max duration + memory usage
- 🌐 Sends data automatically every 10 seconds to Watchlog agent
- 💡 Does not block response time (`flush()` runs after request)
- 📦 PSR-4 autoloaded & production-safe

---

## 📦 Installation

In your Laravel project:

```bash
composer config repositories.watchlog-apm path ../laravel-watchlog-apm
composer require "watchlog/laravel-apm:*"

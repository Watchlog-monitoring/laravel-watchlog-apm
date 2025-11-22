<?php

namespace Watchlog\LaravelAPM\Middleware;

use Closure;
use Illuminate\Http\Request;
use Watchlog\LaravelAPM\Sender;
use Watchlog\LaravelAPM\Collector;

class WatchlogAPM
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $service = env('WATCHLOG_APM_SERVICE') ?? env('APP_NAME', 'laravel-app');
        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000;

        // مسیر واقعی route، مثل hello/{id}
        $path = $request->route()?->uri() ?? $request->path();

        $metric = [
            'type' => 'request',
            'service' => $service,
            'path' => $path,
            'method' => $request->method(),
            'statusCode' => $response->getStatusCode(),
            'duration' => round($duration, 2),
            'timestamp' => date('c'),
            'memory' => [
                'rss' => memory_get_usage(),
                'heapUsed' => memory_get_peak_usage(),
                'heapTotal' => memory_get_usage(true)
            ]
        ];

        // ذخیره متریک در فایل بافر
        Collector::record($metric);

        // ارسال فقط اگر 10 ثانیه از flush قبلی گذشته
        register_shutdown_function(function () {
            $lockFile = storage_path('framework/cache/watchlog-apm.lock');
            $now = time();
            $last = file_exists($lockFile) ? (int) file_get_contents($lockFile) : 0;

            if ($now - $last >= 10) {
                file_put_contents($lockFile, $now);
                // Get agent URL from config or environment, fallback to auto-detection
                $agentUrl = config('watchlog.apm.agent_url') ?? env('WATCHLOG_APM_AGENT_URL', '');
                $sender = new Sender($agentUrl);
                $sender->flush();
            }
        });

        return $response;
    }
}

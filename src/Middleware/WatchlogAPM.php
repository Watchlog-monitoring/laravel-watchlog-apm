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


        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000;

        $metric = [
            'type' => 'request',
            'service' => 'laravel-app',
            'path' => $request->path(),
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

        Collector::record($metric);

        // ارسال خودکار فقط هر ۱۰ ثانیه
        register_shutdown_function(function () {
            static $lastFlush = 0;
            $now = time();

            if ($now - $lastFlush >= 10) {

                $sender = new Sender();
                $sender->flush();

                $lastFlush = $now;

            }
        });

        return $response;
    }
}

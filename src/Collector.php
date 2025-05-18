<?php

namespace Watchlog\LaravelAPM;

class Collector
{
    protected static string $bufferFile = '';

    protected static function getBufferFile(): string
    {
        if (!self::$bufferFile) {
            self::$bufferFile = storage_path('logs/apm-buffer.json');
        }

        return self::$bufferFile;
    }

    public static function record(array $metric): void
    {
        $file = self::getBufferFile();
        file_put_contents($file, json_encode($metric) . "\n", FILE_APPEND);
    }

    public static function flush(): array
    {
        $file = self::getBufferFile();

        if (!file_exists($file)) return [];

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) return [];

        // پاک‌سازی فایل بعد از خواندن
        file_put_contents($file, '');

        $grouped = [];

        foreach ($lines as $line) {
            $metric = json_decode($line, true);
            if (!is_array($metric) || ($metric['type'] ?? '') !== 'request') continue;

            $key = "{$metric['service']}|{$metric['path']}|{$metric['method']}";
            $group = &$grouped[$key];

            if (!isset($group)) {
                $group = [
                    'type' => 'aggregated_request',
                    'service' => $metric['service'],
                    'path' => $metric['path'],
                    'method' => $metric['method'],
                    'request_count' => 0,
                    'error_count' => 0,
                    'total_duration' => 0,
                    'max_duration' => 0,
                    'total_memory' => [
                        'rss' => 0,
                        'heapUsed' => 0,
                        'heapTotal' => 0
                    ]
                ];
            }

            $group['request_count']++;
            if (($metric['statusCode'] ?? 0) >= 500) {
                $group['error_count']++;
            }

            $group['total_duration'] += $metric['duration'] ?? 0;
            $group['max_duration'] = max($group['max_duration'], $metric['duration'] ?? 0);

            if (!empty($metric['memory'])) {
                $group['total_memory']['rss'] += $metric['memory']['rss'] ?? 0;
                $group['total_memory']['heapUsed'] += $metric['memory']['heapUsed'] ?? 0;
                $group['total_memory']['heapTotal'] += $metric['memory']['heapTotal'] ?? 0;
            }
        }

        // خروجی نهایی آماده ارسال به Agent
        $result = [];

        foreach ($grouped as $group) {
            $count = max(1, $group['request_count']);

            $result[] = [
                'type' => $group['type'],
                'service' => $group['service'],
                'path' => $group['path'],
                'method' => $group['method'],
                'request_count' => $group['request_count'],
                'error_count' => $group['error_count'],
                'avg_duration' => round($group['total_duration'] / $count, 2),
                'max_duration' => round($group['max_duration'], 2),
                'avg_memory' => [
                    'rss' => (int) ($group['total_memory']['rss'] / $count),
                    'heapUsed' => (int) ($group['total_memory']['heapUsed'] / $count),
                    'heapTotal' => (int) ($group['total_memory']['heapTotal'] / $count)
                ]
            ];
        }

        return $result;
    }
}

<?php

namespace Watchlog\LaravelAPM;

class Collector
{
    protected static array $grouped = [];

    public static function record(array $metric): void
    {
        if ($metric['type'] !== 'request') return;

        $key = "{$metric['service']}|{$metric['path']}|{$metric['method']}";
        $group = &self::$grouped[$key];

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
        if ($metric['statusCode'] >= 500) {
            $group['error_count']++;
        }

        $group['total_duration'] += $metric['duration'];
        $group['max_duration'] = max($group['max_duration'], $metric['duration']);

        $mem = $metric['memory'];
        $group['total_memory']['rss'] += $mem['rss'];
        $group['total_memory']['heapUsed'] += $mem['heapUsed'];
        $group['total_memory']['heapTotal'] += $mem['heapTotal'];
    }

    public static function flush(): array
    {
        $result = [];

        foreach (self::$grouped as $group) {
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

        self::$grouped = []; // reset
        return $result;
    }
}

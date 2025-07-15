<?php

namespace Watchlog\LaravelAPM;

use Illuminate\Support\Facades\Http;

class Sender
{
    protected string $agentUrl;

    /** @var bool|null */
    protected static ?bool $isK8s = null;

    /** @var string|null */
    protected static ?string $cachedUrl = null;

    public function __construct(string $agentUrl = '')
    {
        $this->agentUrl = $agentUrl !== ''
            ? $agentUrl
            : self::detectAgentUrl();
    }

    protected static function isRunningInK8s(): bool
    {
        if (self::$isK8s !== null) {
            return self::$isK8s;
        }

        // روش 1: ServiceAccount Token
        if (is_file('/var/run/secrets/kubernetes.io/serviceaccount/token')) {
            return self::$isK8s = true;
        }

        // روش 2: بررسی cgroup
        try {
            $content = file_get_contents('/proc/1/cgroup');
            if (strpos($content, 'kubepods') !== false) {
                return self::$isK8s = true;
            }
        } catch (\Throwable) {
            // silent
        }

        // روش 3: DNS lookup
        try {
            $ip = gethostbyname('kubernetes.default.svc.cluster.local');
            if ($ip !== 'kubernetes.default.svc.cluster.local') {
                return self::$isK8s = true;
            }
        } catch (\Throwable) {
            // silent
        }

        return self::$isK8s = false;
    }

    protected static function detectAgentUrl(): string
    {
        if (self::$cachedUrl !== null) {
            return self::$cachedUrl;
        }

        self::$cachedUrl = self::isRunningInK8s()
            ? 'http://watchlog-node-agent.monitoring.svc.cluster.local:3774/apm'
            : 'http://127.0.0.1:3774/apm';

        return self::$cachedUrl;
    }

    public function flush(): void
    {
        $metrics = Collector::flush();

        if (empty($metrics)) {
            return;
        }

        $payload = [
            'collected_at' => now()->toIso8601String(),
            'platformName' => 'laravel',
            'metrics'      => $metrics,
        ];

        try {
            Http::timeout(3)
                ->post($this->agentUrl, $payload);
        } catch (\Throwable) {
            // silent
        }
    }
}

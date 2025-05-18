<?php

namespace Watchlog\LaravelAPM;

class Sender
{
    protected string $agentUrl;

    public function __construct(string $agentUrl = 'http://localhost:3774/apm')
    {
        $this->agentUrl = $agentUrl;
    }

    public function flush(): void
    {
        $metrics = Collector::flush();

        if (empty($metrics)) {
            return; // چیزی برای ارسال وجود ندارد
        }

        $payload = json_encode([
            'collected_at' => date('c'),
            'platformName' => 'laravel',
            'metrics' => $metrics
        ]);

        try {
            $ch = curl_init($this->agentUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            // ارسال ناموفق، می‌تونی اینجا لاگ بزنی به فایل error مثلا
            // file_put_contents(storage_path('logs/apm-error.log'), $e->getMessage(), FILE_APPEND);
        }
    }
}

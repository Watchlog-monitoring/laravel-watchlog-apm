<?php

namespace Watchlog\LaravelAPM;

class Sender
{
    protected $agentUrl;

    public function __construct($agentUrl = 'http://localhost:3774/apm')
    {
        $this->agentUrl = $agentUrl;
    }

    public function flush()
    {
        $metrics = Collector::flush();

        if (empty($metrics)) return;

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
        } catch (\Exception $e) {
            // optional: log to apm-error.log
        }
    }
}

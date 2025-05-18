<?php

namespace Watchlog\LaravelAPM;

use Illuminate\Support\ServiceProvider;

class WatchlogAPMServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Watchlog\LaravelAPM\Console\FlushMetricsCommand::class,
            ]);
        }

    }
}

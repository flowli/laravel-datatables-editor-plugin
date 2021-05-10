<?php

namespace arweb\DataTablesEditor;

use arweb\DataTablesEditor\Commands\DTECreateConfigCommand;
use Illuminate\Support\ServiceProvider;

// see https://laravel.com/docs/8.x/packages#service-providers
class DTEServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/Views', 'dte');
        if ($this->app->runningInConsole()) {
            $this->commands([
                DTECreateConfigCommand::class
            ]);
        }
    }
}

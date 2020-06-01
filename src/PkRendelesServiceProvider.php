<?php

namespace Xprofit\PkRendeles;

use Illuminate\Support\ServiceProvider;

class PkRendelesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //$this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views', 'pkrendeles');
        $this->mergeConfigFrom(__DIR__.'/config/pk_rendeles.php', 'pkrendeles');
        $this->publishes([__DIR__.'/config/pk_rendeles.php' => config_path('pk_rendeles.php'),]);
    }

    public function register()
    {

    }
}


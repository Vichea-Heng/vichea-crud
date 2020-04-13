<?php

namespace Vichea\Crud;

use Illuminate\Support\ServiceProvider;


class CrudServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__ . "/resources/stubs" => "resources/stubs",
            // __DIR__."/Console/Commands/CrudApiCommand.php" => app_path("Console/Commands/CrudApiCommand.php"),

        ]);
    }

    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudApiCommand::class,
            ]);
        }
    }
}

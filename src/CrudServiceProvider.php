<?php

namespace Vichea\Crud;

use App\Console\Commands\CrudApiCommand;
use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->commands([
            CrudApiCommand::class,
        ]);
        $this->publishes([
            __DIR__ . "/resources/stubs" => "resources/stubs",
            // __DIR__."/Console/Commands/CrudApiCommand.php" => app_path("Console/Commands/CrudApiCommand.php"),

        ]);
    }

    public function register()
    {
    }
}

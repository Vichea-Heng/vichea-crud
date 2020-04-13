<?php

namespace Vichea\Crud;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CrudApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * 
     */
    protected $signature = 'crud:api-generator {name} {--policy} {--request} {--resource} {--factory} {--seeder} {--model} {--controller} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD for API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name_with_dir = $this->argument("name");
        $split = preg_split("#/#", $name_with_dir);
        $name = end($split);
        $name_snake_case = $this->from_camel_case($name);
        $dir = ($tmp_dir = substr($name_with_dir, 0, - (strlen($name) + 1))) ? $tmp_dir : "";
        ($dir == "") ? "" : ($dir = "\\" . $dir);
        $dir = str_replace('/', '\\', $dir);
        $name_with_dir = str_replace('/', '\\', $name_with_dir);

        if ($this->option("all")) {
            $this->model($name, $dir);
            $this->controller($name, $name_snake_case, $name_with_dir, $dir);
            $this->request($name, $dir);
            $this->resource($name, $dir);
            $this->factory($name, $name_with_dir);
            $this->seeder($name, $name_snake_case, $name_with_dir);

            $name_with_dir = str_replace('\\', '\\\\', $name_with_dir);

            File::append(
                base_path("routes/api.php"),
                "\nRoute::apiResource('/$name_snake_case', '{$name_with_dir}Controller');
                Route::get('/$name_snake_case/index/only_trashed' , '{$name_with_dir}Controller@indexOnlyTrashed'); 
                Route::post('/$name_snake_case/restore/{{$name_snake_case}}' , '{$name_with_dir}Controller@restore');
                Route::delete('/$name_snake_case/forceDelete/{{$name_snake_case}}' , '{$name_with_dir}Controller@forceDestroy'); "
            );

            Artisan::call("make:migration create_" . Str::plural($name_snake_case) . "_table --create=" . Str::plural($name_snake_case));
        } else {
            if ($this->option("model")) {
                $this->model($name, $dir);
            }
            if ($this->option("controller")) {
                $this->controller($name, $name_snake_case, $name_with_dir, $dir);
            }
            if ($this->option("request")) {
                $this->request($name, $dir);
            }
            if ($this->option("resource")) {
                $this->resource($name, $dir);
            }
            if ($this->option("policy")) {
                $this->policy($name, $name_snake_case, $name_with_dir, $dir);
            }
            if ($this->option("factory")) {
                $this->factory($name, $name_with_dir);
            }
            if ($this->option("seeder")) {
                $this->seeder($name, $name_snake_case, $name_with_dir);
            }
        }
    }

    public function controller($name, $name_snake_case, $name_with_dir, $dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelSnakeCaseName}}', '{{modelNameWithDir}}', '{{modelDir}}'],
            [$name, $name_snake_case, $name_with_dir, $dir],
            file_get_contents(resource_path("stubs/Controller.stub"))
        );

        $dir = str_replace('\\', '/', $dir);

        if (!file_exists($path = app_path("Http/Controllers" . $dir)))
            mkdir($path, 0777, true);

        file_put_contents(app_path("Http/Controllers{$dir}/{$name}Controller.php"), $modelTemplate);
    }

    public function seeder($name, $name_snake_case, $name_with_dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelSnakeCaseName}}', '{{modelNameWithDir}}'],
            [$name, Str::plural($name_snake_case), $name_with_dir],
            file_get_contents(resource_path("stubs/Seeder.stub"))
        );

        if (!file_exists($path = base_path("database/seeds")))
            mkdir($path, 0777, true);

        file_put_contents(base_path("database/seeds/{$name}Seeder.php"), $modelTemplate);
    }

    public function policy($name, $name_snake_case, $name_with_dir, $dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelSnakeCaseName}}', '{{modelNameWithDir}}', '{{modelDir}}'],
            [$name, $name_snake_case, $name_with_dir, $dir],
            file_get_contents(resource_path("stubs/Policy.stub"))
        );

        $dir = str_replace('\\', '/', $dir);

        if (!file_exists($path = app_path("Policies" . $dir)))
            mkdir($path, 0777, true);

        file_put_contents(app_path("Policies{$dir}/{$name}Policy.php"), $modelTemplate);
    }

    public function request($name, $dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelDir}}'],
            [$name, $dir],
            file_get_contents(resource_path("stubs/Request.stub"))
        );

        $dir = str_replace('\\', '/', $dir);

        if (!file_exists($path = app_path("Http/Requests" . $dir)))
            mkdir($path, 0777, true);

        file_put_contents(app_path("Http/Requests{$dir}/{$name}Request.php"), $modelTemplate);
    }

    public function resource($name, $dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelDir}}'],
            [$name, $dir],
            file_get_contents(resource_path("stubs/Resource.stub"))
        );

        $dir = str_replace('\\', '/', $dir);

        if (!file_exists($path = app_path("Http/Resources" . $dir)))
            mkdir($path, 0777, true);

        file_put_contents(app_path("Http/Resources{$dir}/{$name}Resource.php"), $modelTemplate);
    }

    public function model($name, $dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelDir}}'],
            [$name, $dir],
            file_get_contents(resource_path("stubs/Model.stub"))
        );

        $dir = str_replace('\\', '/', $dir);

        if (!file_exists($path = app_path("Models" . $dir)))
            mkdir($path, 0777, true);

        file_put_contents(app_path("Models{$dir}/{$name}.php"), $modelTemplate);
    }

    public function factory($name, $name_with_dir)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{modelNameWithDir}}'],
            [$name, $name_with_dir],
            file_get_contents(resource_path("stubs/Factory.stub"))
        );

        if (!file_exists($path = base_path("database/factories")))
            mkdir($path, 0777, true);

        file_put_contents(base_path("database/factories/{$name}Factory.php"), $modelTemplate);
    }









    public function from_camel_case($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}

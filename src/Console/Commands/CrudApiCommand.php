<?php

namespace App\Console\Commands;

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
        $name = $this->argument("name"); 
        $name_snake_case = $this->from_camel_case($name);

        if($this->option("all")){
            $this->model($name);
            $this->controller($name,$name_snake_case);
            $this->request($name);
            $this->resource($name);
            $this->factory($name);
            $this->seeder($name,$name_snake_case);

            File::append(base_path("routes/api.php"), 
                "\nRoute::apiResource('/$name_snake_case', '{$name}Controller');
                \nRoute::get('/$name_snake_case/index/only_trashed' , '{$name}Controller@indexOnlyTrashed'); 
                \nRoute::post('/$name_snake_case/restore/{{$name_snake_case}}' , '{$name}Controller@restore');
                \nRoute::delete('/$name_snake_case/forceDelete/{{$name_snake_case}}' , '{$name}Controller@forceDestroy'); "
            );

            Artisan::call("make:migration create_".Str::plural($name_snake_case)."_table --create=".Str::plural($name_snake_case));
        }
        else{
            if($this->option("model")){
                $this->model($name);
            }
            if($this->option("controller")){
                $this->controller($name,$name_snake_case);
            }
            if($this->option("request")){
                $this->request($name);
            }
            if($this->option("resource")){
                $this->resource($name);
            }
            if($this->option("policy")){
                $this->policy($name,$name_snake_case);
            }
            if($this->option("factory")){
                $this->factory($name);
            }
            if($this->option("seeder")){
                $this->seeder($name,$name_snake_case);
            }
        }

    }

    public function controller($name,$name_snake_case){
        $modelTemplate = str_replace(
            ['{{modelName}}','{{modelSnakeCaseName}}'],
            [$name,$name_snake_case],
            file_get_contents(resource_path("stubs/Controller.stub"))
        ); 
        
        if(!file_exists($path = app_path("Http/Controllers")))
            mkdir($path, 0777 , true);

        file_put_contents(app_path("Http/Controllers/{$name}Controller.php"), $modelTemplate);
    }

    public function seeder($name,$name_snake_case){
        $modelTemplate = str_replace(
            ['{{modelName}}','{{modelSnakeCaseName}}'],
            [$name,$name_snake_case],
            file_get_contents(resource_path("stubs/Seeder.stub"))
        ); 
        
        if(!file_exists($path = base_path("database/seeds")))
            mkdir($path, 0777 , true);

        file_put_contents(base_path("database/seeds/{$name}Seeder.php"), $modelTemplate);
    }

    public function policy($name,$name_snake_case){
        $modelTemplate = str_replace(
            ['{{modelName}}','{{modelSnakeCaseName}}'],
            [$name,$name_snake_case],
            file_get_contents(resource_path("stubs/Policy.stub"))
        ); 
        
        if(!file_exists($path = app_path("Policies")))
            mkdir($path, 0777 , true);

        file_put_contents(app_path("Policies/{$name}Policy.php"), $modelTemplate);
    }

    public function request($name){
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            file_get_contents(resource_path("stubs/Request.stub"))
        ); 
        
        if(!file_exists($path = app_path("Http/Requests")))
            mkdir($path, 0777 , true);

        file_put_contents(app_path("Http/Requests/{$name}Request.php"), $modelTemplate);
    }

    public function resource($name){
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            file_get_contents(resource_path("stubs/Resource.stub"))
        ); 
        
        if(!file_exists($path = app_path("Http/Resources")))
            mkdir($path, 0777 , true);

        file_put_contents(app_path("Http/Resources/{$name}Resource.php"), $modelTemplate);
    }
    
    public function model($name){
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            file_get_contents(resource_path("stubs/Model.stub"))
        ); 
        
        if(!file_exists($path = app_path("Models")))
            mkdir($path, 0777 , true);

        file_put_contents(app_path("Models/{$name}.php"), $modelTemplate);
    }

    public function factory($name){
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            file_get_contents(resource_path("stubs/Factory.stub"))
        ); 
        
        if(!file_exists($path = base_path("database/factories")))
            mkdir($path, 0777 , true);

        file_put_contents(base_path("database/factories/{$name}Factory.php"), $modelTemplate);
    }








    
    public function from_camel_case($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
          $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
      }

}
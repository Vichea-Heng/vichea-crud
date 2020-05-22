<?php

namespace Vichea\Crud;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Filesystem\FileExistsException;

class CrudApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-crud {name} {--options=a : m=Model,c=Controller,r=Resource,f=FormRequest,a=All}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate api crud based on table. Note: should have table in advance.';

    protected $tableInfo;
    protected $stubsPath;
    protected $name;
    protected $dir;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->stubsPath = app_path('Console/Commands/stubs/crud/');

        DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->name = last(explode('/', $this->argument('name')));
        $this->dir = substr($this->argument('name'), 0, strrpos($this->argument('name'), '/') + 1);

        $this->tableInfo = DB::select('describe ' . $this->ask('What is your table name?'));

        if (Str::contains($this->option('options'), ['a', 'm']))
            $this->generateModel();

        if (Str::contains($this->option('options'), ['a', 'c']))
            $this->generateController();

        if (Str::contains($this->option('options'), ['a', 'f']))
            $this->generateFormRequest();

        if (Str::contains($this->option('options'), ['a', 'p']))
            $this->generatePolicy();

        if (Str::contains($this->option('options'), ['a', 'r']))
            $this->generateResource();
    }

    private function generateModel()
    {
        $modelDir = substr(str_replace('/', '\\', $this->dir), 0, -1);
        $content = str_replace(
            ['{{modelDir}}', '{{modelName}}'],
            [$modelDir, $this->name],
            file_get_contents($this->stubsPath . 'Model.stub')
        );

        $dir = $this->getModelsPath() . $this->dir;

        $this->generate($dir, $content);

        $this->line("<fg=green>Model generated:\n{$dir}{$this->name}.php</>\n");
    }

    private function generateController()
    {
        $modelDir = substr(str_replace('/', '\\', $this->dir), 0, -1);
        $content = str_replace(
            ['{{modelName}}', '{{modelCamelCaseName}}', '{{modelDir}}'],
            [$this->name, Str::camel($this->name), $modelDir],
            file_get_contents($this->stubsPath . 'Controller.stub')
        );

        $dir = $this->getControllersPath() . $this->dir;

        $this->generate($dir, $content);

        $this->line("<fg=green>Controller generated:\n{$dir}{$this->name}.php</>\n");
    }

    private function generatePolicy()
    {
        $modelDir = substr(str_replace('/', '\\', $this->dir), 0, -1);
        $content = str_replace(
            ['{{modelName}}', '{{modelCamelCaseName}}', '{{modelDir}}'],
            [$this->name, Str::camel($this->name), $modelDir],
            file_get_contents($this->stubsPath . 'Policy.stub')
        );

        $dir = $this->getPoliciesPath() . $this->dir;

        $this->generate($dir, $content);

        $this->line("<fg=green>Policy generated:\n{$dir}{$this->name}.php</>\n");
    }

    private function generateFormRequest()
    {
        $modelDir = substr(str_replace('/', '\\', $this->dir), 0, -1);
        $content = str_replace(
            ['{{modelName}}', '{{modelDir}}', '{{rules}}'],
            [$this->name, $modelDir, $this->getAllRules()],
            file_get_contents($this->stubsPath . 'Request.stub')
        );

        $dir = $this->getRequestsPath() . $this->dir;

        $this->generate($dir, $content);

        $this->line("<fg=green>Form request generated:\n{$dir}{$this->name}.php</>\n");
    }

    private function generateResource()
    {
        $modelDir = substr(str_replace('/', '\\', $this->dir), 0, -1);
        $content = str_replace(
            ['{{modelName}}', '{{modelDir}}', '{{columns}}'],
            [$this->name, $modelDir, $this->getAllResourceColumns()],
            file_get_contents($this->stubsPath . 'Resource.stub')
        );

        $dir = $this->getResourcesPath() . $this->dir;

        $this->generate($dir, $content);

        $this->line("<fg=green>Resource generated:\n{$dir}{$this->name}.php</>\n");
    }

    private function generate($fullDirPath, $content)
    {
        if (file_exists($fullDirPath . $this->name . '.php'))
            throw new FileExistsException();

        if (!file_exists($fullDirPath))
            mkdir($fullDirPath, 0777, true);

        file_put_contents($fullDirPath . $this->name . '.php', $content);
    }

    private function getAllResourceColumns(): string
    {
        $allColumns = [];
        foreach ($this->tableInfo as $column) {
            if (!in_array($column->Field, ['created_at', 'updated_at', 'deleted_at']))
                $allColumns[$column->Field] = '$this->' . $column->Field;
        }

        $format = '';
        foreach ($allColumns as $key => $val)
            $format .= "'$key' => $val," . PHP_EOL;

        return $format;
    }

    private function getAllRules(): string
    {
        $allRules = [];
        foreach ($this->tableInfo as $column) {
            if (!in_array($column->Field, ['id', 'created_at', 'updated_at', 'deleted_at']))
                $allRules[$column->Field] =  '[\'' . implode("', '", $this->getRulesByColumn($column)) . '\']';
        }

        $format = '';
        foreach ($allRules as $key => $val)
            $format .= "'$key' => $val," . PHP_EOL;

        return $format;
    }

    private function getRulesByColumn($column)
    {
        $rules = collect('bail');
        if ($column->Null == 'NO')
            $rules->add('required');

        $rules->add($this->getRulesByType($column->Type));

        return $rules->flatten(1)->toArray();
    }

    /**
     * currently does not support unsigned
     */
    private function getRulesByType($type)
    {
        $rules = collect();

        $mainType = explode(' ', $type)[0];
        if (Str::startsWith($mainType, ['tinyint', 'smallint', 'int', 'bigint']))
            $rules->add('integer');
        elseif (Str::startsWith($mainType, 'json'))
            $rules->add('json');
        elseif (Str::startsWith($mainType, 'enum')) {
            $rules->add('in:' . str_replace('\'', '', substr($mainType, strpos($mainType, '(') + 1, -1)));
        } elseif (Str::startsWith($mainType, 'varchar')) {
            $rules->add('string');
            $rules->add('max:' . substr($mainType, strpos($mainType, '(') + 1, -1));
        } elseif (Str::startsWith($mainType, 'timestamp')) {
            $rules->add('date');
        }

        return $rules;
    }

    private function getModelsPath()
    {
        return app_path('Models/');
    }

    private function getControllersPath()
    {
        return app_path('Http/Controllers/Api/v1/');
    }

    private function getResourcesPath()
    {
        return app_path('Http/Resources/');
    }

    private function getRequestsPath()
    {
        return app_path('Http/Requests/');
    }

    private function getPoliciesPath()
    {
        return app_path('Policies/');
    }
}

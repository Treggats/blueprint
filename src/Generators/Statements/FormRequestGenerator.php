<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Column;
use Blueprint\Contracts\Generator;
use Blueprint\Model;
use Blueprint\Models\Statements\ValidateStatement;
use Blueprint\Translators\Rules;
use Illuminate\Support\Str;

class FormRequestGenerator implements Generator
{
    private const INDENT = '            ';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    private $models = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->get(STUBS_PATH . '/form-request.stub');

        if (isset($tree['models'])) {
            $this->registerModels($tree['models']);
        }

        /** @var \Blueprint\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof ValidateStatement) {
                        continue;
                    }

                    $context = $this->getContextFromController($controller->name());
                    $name = $this->getName($context, $method);
                    $path = $this->getPath($name);

                    if ($this->files->exists($path)) {
                        continue;
                    }

                    $this->files->put(
                        $path,
                        $this->populateStub($stub, $name, $context, $statement)
                    );

                    $output['created'][] = $path;
                }
            }
        }

        return $output;
    }

    protected function getPath(string $name)
    {
        return 'app/Http/Requests/' . $name . '.php';
    }

    protected function populateStub(string $stub, string $name, $context, ValidateStatement $validateStatement)
    {
        $stub = str_replace('DummyNamespace', 'App\\Http\\Requests', $stub);
        $stub = str_replace('DummyClass', $name, $stub);
        $stub = str_replace('// rules...', $this->buildRules($context, $validateStatement), $stub);

        return $stub;
    }

    private function buildRules(string $context, ValidateStatement $validateStatement)
    {
        return trim(array_reduce($validateStatement->data(), function ($output, $field) use ($context) {
            [$qualifier, $column] = $this->splitField($field);

            if (is_null($qualifier)) {
                $qualifier = $context;
            }

            $rules = $this->validationRules($qualifier, $column);

            $output .= self::INDENT . "'{$column}' => '{$rules}'," . PHP_EOL;
            return $output;
        }, ''));
    }

    private function getContextFromController(string $name)
    {
        $context = $name;

        if (Str::endsWith($name, 'Controller')) {
            $context = Str::substr($name, 0, -10);
        }

        return Str::singular($context);
    }

    private function modelForContext(string $context)
    {
        return $this->models[Str::studly($context)] ?? $this->models[Str::lower($context)];
    }

    private function getName(string $context, string $method)
    {
        return $context . Str::studly($method) . 'Request';
    }

    private function splitField($field)
    {
        if (Str::contains($field, '.')) {
            return explode('.', $field, 2);
        }

        return [null, $field];
    }

    private function validationRules(string $qualifier, string $column)
    {
        /** @var Model $model */
        $model = $this->modelForContext($qualifier);

        if (!is_null($model) && $model->hasColumn($column)) {
            $column = $model->column($column);

            return implode('|', Rules::fromColumn($column));
        }

        return 'required';
    }

    private function registerModels(?array $models)
    {
        $this->models = $models ?? [];
    }


}

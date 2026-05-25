<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\LaravelBootstrapper;
use AndyDefer\Records\Collections\Utility\StringTypedCollection;
use Illuminate\Filesystem\Filesystem;

class MakeActionDirective extends AbstractDirective
{
    private Filesystem $files;

    public function __construct(
        DirectiveInteractionService $interaction,
        ?LaravelBootstrapper $laravelBootstrapper = null,
    ) {
        parent::__construct($interaction, $laravelBootstrapper);
        $this->files = new Filesystem();
    }

    public function getSignature(): string
    {
        return 'make-action {name : The name of the action (e.g., Users/ShowUserAction)} 
                       {--type=api : Action type (api|web)} 
                       {--force : Overwrite existing files}';
    }

    public function getDescription(): string
    {
        return 'Create a new Action class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection();
        $aliases->add('action-make');
        $aliases->add('create-action');
        return $aliases;
    }

    public function shouldBootLaravel(): bool
    {
        return true;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        $force = $this->hasOption('force');

        if ($name === null) {
            $this->error('Action name is required.');
            return ExitCode::FAILURE;
        }

        if (!in_array($type, ['api', 'web'])) {
            $this->error("Invalid type '{$type}'. Allowed types: api, web");
            return ExitCode::FAILURE;
        }

        $this->info("Creating {$type} action: {$name}");

        if (!$this->createAction($name, $type, $force)) {
            return ExitCode::FAILURE;
        }

        $this->info("Action '{$name}' created successfully!");

        return ExitCode::SUCCESS;
    }

    private function createAction(string $name, string $type, bool $force): bool
    {
        $path = $this->getActionPath($name);
        $namespace = $this->getActionNamespace($name);
        $className = $this->getClassName($name);

        if ($this->files->exists($path) && !$force) {
            $this->error("Action already exists at: {$path}");
            return false;
        }

        $stub = $this->getStub("action.{$type}.stub");
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        $this->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);

        return true;
    }

    private function getActionPath(string $name): string
    {
        $basePath = app_path('Actions');
        $segments = explode('/', $name);
        $className = array_pop($segments);

        if (!empty($segments)) {
            $basePath .= '/' . implode('/', $segments);
        }

        return "{$basePath}/{$className}.php";
    }

    private function getActionNamespace(string $name): string
    {
        $segments = explode('/', $name);
        array_pop($segments);

        $baseNamespace = 'App\\Actions';

        if (!empty($segments)) {
            $baseNamespace .= '\\' . implode('\\', $segments);
        }

        return $baseNamespace;
    }

    private function getClassName(string $name): string
    {
        $segments = explode('/', $name);
        return array_pop($segments);
    }

    private function getStub(string $name): string
    {
        $stubPath = __DIR__ . '/../../stubs/' . $name;
        return $this->files->get($stubPath);
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }
}

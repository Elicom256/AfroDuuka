<?php

namespace App\AI;

use Illuminate\Support\Str;

class ToolRegistry
{
    protected array $tools = [];

    public function __construct()
    {
        $this->discoverTools();
    }

    protected function discoverTools(): void
    {
        $namespace = 'App\\AI\\Tools\\';
        $path = __DIR__ . '/Tools';

        foreach (glob($path . '/*.php') as $file) {
            $class = $namespace . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Tool::class)) {
                continue;
            }

            /** @var Tool $instance */
            $instance = $reflection->newInstance();
            $this->register($instance);
        }
    }

    public function register(Tool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function all(): array
    {
        return $this->tools;
    }

    public function find(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    public function definitions(): array
    {
        return array_map(fn (Tool $tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'parameters' => $tool->parameters(),
        ], $this->tools);
    }
}

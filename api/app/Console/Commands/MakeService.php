<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeService extends Command
{
    protected $signature = 'make:service {name} {--force}';
    protected $description = 'Create a new service class';

    public function handle()
    {
        $name = $this->argument('name');

        // Ensure proper class name
        $className = Str::studly($name);

        $directory = app_path('Services');
        $path = $directory . '/' . $className . '.php';

        // Check if exists
        if (file_exists($path) && !$this->option('force')) {
            $this->error("Service already exists!");
            return;
        }

        // Create directory if missing
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Stub content
        $stub = $this->getStub($className);

        file_put_contents($path, $stub);

        $this->info("Service {$className} created successfully.");
    }

    protected function getStub($className)
    {
        return <<<PHP
<?php

namespace App\Services;

class {$className}
{
    public function __construct()
    {
        //
    }
}
PHP;
    }
}
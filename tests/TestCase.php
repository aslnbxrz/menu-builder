<?php

namespace Aslnbxrz\MenuBuilder\Tests;

use Aslnbxrz\MenuBuilder\MenuBuilderServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Aslnbxrz\\MenuBuilder\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            MenuBuilderServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Load package migrations
        $migrationPath = __DIR__ . '/../database/migrations';
        if (is_dir($migrationPath)) {
            foreach (\Illuminate\Support\Facades\File::allFiles($migrationPath) as $migration) {
                (include $migration->getRealPath())->up();
            }
        }
    }
}

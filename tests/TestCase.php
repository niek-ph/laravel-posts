<?php

namespace NiekPH\LaravelPosts\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use NiekPH\LaravelPosts\LaravelPostsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'NiekPH\\LaravelPosts\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelPostsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config) {
            $config->set('session.encrypt', false);
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

        });
    }
}

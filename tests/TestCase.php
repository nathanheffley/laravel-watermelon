<?php

namespace NathanHeffley\LaravelWatermelon\Tests;

use NathanHeffley\LaravelWatermelon\WatermelonServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            WatermelonServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database');
    }
}

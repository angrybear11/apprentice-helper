<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
          'driver'   => 'sqlite',
          'database' => ':memory:',
          'prefix'   => '',
      ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            'Voronoi\Apprentice\ServiceProvider',
        ];
    }
}

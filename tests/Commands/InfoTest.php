<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Voronoi\Apprentice\CommandProvider;
use Voronoi\Apprentice\Libraries\PackageReader;
use Voronoi\Apprentice\Transformers\Command as CommandTransformer;
use Voronoi\Apprentice\Console\Commands\Info;

class InfoTest extends TestCase
{
    use RefreshDatabase;

    public function testNoCommands()
    {
        $provider = Mockery::mock(CommandProvider::class);
        $provider->shouldReceive('allowedCommands')->once()->andReturn([]);
        app()->instance(CommandProvider::class, $provider);

        $this->artisan('apprentice:info')
           ->expectsOutput('No commands');
    }

    public function testReturnsVersionAndCommands()
    {
        $provider = Mockery::mock(CommandProvider::class);
        $packageReader = Mockery::mock(PackageReader::class);
        $transformer = Mockery::mock(CommandTransformer::class);
        $command = new Info($provider, $packageReader, $transformer);

        $provider->shouldReceive('allowedCommands')->once()->andReturn([$command]);
        app()->instance(CommandProvider::class, $provider);

        $packageReader->shouldReceive('version')->once()->andReturn('1.0.1');
        app()->instance(PackageReader::class, $packageReader);

        $transformer->shouldReceive('transformCollection')->once()->andReturn([$command->getName()]);
        app()->instance(CommandTransformer::class, $transformer);

        $this->artisan('apprentice:info')
              ->expectsOutput('{"version":"1.0.1","commands":["apprentice:info"]}');
    }
}

<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\CommandProvider;
use Mockery;
use Artisan;

class CommandProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testUsesArtisan()
    {
        Artisan::shouldReceive("all")->once()->andReturn([]);

        $provider = new CommandProvider;
        $provider->allowedCommands();
    }
}

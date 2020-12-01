<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Artisan\Artisan;
use Illuminate\Console\Command;
use Voronoi\Apprentice\Libraries\Socket\Reader;
use Voronoi\Apprentice\Libraries\Socket\Writer;
use Voronoi\Apprentice\Messenger;
use Mockery;
use Artisan as ArtisanLaravel;
use Voronoi\Apprentice\Message;

class ArtisanTest extends TestCase
{
    use RefreshDatabase;

    public function testSendsDoneAfterRunningCommand()
    {
        $command = Mockery::mock(Command::class);
        $reader = Mockery::mock(Reader::class);
        $writer = Mockery::mock(Writer::class);
        $messenger = Mockery::mock(Messenger::class);

        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) {
            return $argument->name == "done";
        }))->once();

        ArtisanLaravel::shouldReceive('handle')->once();

        $artisan = new Artisan();
        $artisan->call($command, $reader, $writer, $messenger);
    }
}

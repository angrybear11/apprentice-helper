<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Messenger;
use Voronoi\Apprentice\Message;
use Voronoi\Apprentice\Artisan\FileStreamOutput;
use Mockery;

class FileStreamOutputTest extends TestCase
{
    use RefreshDatabase;

    public function testWriteSendsOutputMessage()
    {
        $messenger = Mockery::mock(Messenger::class);
        $message = "a message";
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) use ($message) {
            return $argument->name == "text"
            && $argument->message == ['messages' => [$message]];
        }))->once();

        $output = new FileStreamOutput($messenger);
        $output->write([$message]);
    }
    
    public function testAddNewline()
    {
        $messenger = Mockery::mock(Messenger::class);
        $message = "a message";
        $messenger->shouldReceive('send')->with(Mockery::on(function ($argument) use ($message) {
            return $argument->name == "text"
            && $argument->message == ['messages' => [$message . "\n"]];
        }))->once();

        $output = new FileStreamOutput($messenger);
        $output->write([$message], true);
    }
}

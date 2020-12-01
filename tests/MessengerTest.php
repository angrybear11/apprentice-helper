<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Message;
use Voronoi\Apprentice\Messenger;
use Mockery;
use Voronoi\Apprentice\Libraries\Socket\Writer;

class MessengerTest extends TestCase
{
    use RefreshDatabase;

    public function testSendMessageWritesJSON()
    {
        $writer = Mockery::mock(Writer::class);
        $writer->shouldReceive("write")->once()->with("{\"type\":\"done\",\"data\":\"\"}\n");

        $messenger = new Messenger($writer);
        $message = new Message("done");
        $messenger->send($message);
    }

    public function testPrepareEncodesJSON()
    {
        $writer = Mockery::mock(Writer::class);
        $messenger = new Messenger($writer);

        $message = new Message("done", "message here");
        $result = $messenger->prepare($message);

        $this->assertEquals('{"type":"done","data":"message here"}', $result);
    }
}

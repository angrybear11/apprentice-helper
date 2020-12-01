<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Message;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function testCanCreateWithoutMessage()
    {
        $message = new Message("done");
        $this->assertNotNull($message);
    }
}

<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Session;
use Voronoi\Apprentice\Models\User;
use Mockery;

class SessionTest extends TestCase
{
    use RefreshDatabase;
    
    public function testNotRunningViaApprentice()
    {
        $session = new Session;
        $this->assertFalse($session->runningViaApprentice());
    }
    
    public function testRunningViaApprentice()
    {
        $session = new Session;
        $user = new User;
        $user->id = '1';        
        $session->setUser($user);
        
        $this->assertTrue($session->runningViaApprentice());           
    }
    
    public function testInvalidOutputMessenger()
    {
        $session = new Session;
        $messenger = $session->getOutputMessenger();
        $this->assertNull($messenger);
    }

    public function testValidOutputMessenger()
    {
        $session = new Session;
        $user = new User;
        $user->id = '1';
        $session->setUser($user);
        
        $messenger = $session->getOutputMessenger();
        $this->assertNotNull($messenger);
    }

    public function testNullCurrentUserID()
    {
        $session = new Session;
        $this->assertNull($session->currentUserID());
    }

    public function testGetUserId()
    {
        $session = new Session;

        $user = new User;
        $user->id = '1';

        $session->setUser($user);

        $this->assertEquals('1', $session->currentUserID());
    }
}

<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Artisan\ProgressBar;
use Voronoi\Apprentice\Messenger;
use Mockery;

class ProgressBarTest extends TestCase
{
    use RefreshDatabase;

    public function testTitleAccessors()
    {
        $messenger = $this->mock(Messenger::class);
        $bar = new ProgressBar($messenger);
        
        $bar->setTitle("Loading");
        $this->assertEquals("Loading", $bar->getTitle());
    }
    
    public function testGetMaxSteps()
    {
        $messenger = $this->mock(Messenger::class);
        $bar = new ProgressBar($messenger, 5);
        
        $this->assertEquals(5, $bar->getMaxSteps());
    }
    
    public function testAdvance()
    {
        $messenger = $this->mock(Messenger::class);
        $messenger->shouldReceive('send')->once();
        $bar = new ProgressBar($messenger, 5);

        $bar->advance(2);
        $this->assertEquals(2, $bar->getProgress());
    }
    
    public function testAdvanceBeyondMax()
    {
        $messenger = $this->mock(Messenger::class);
        $messenger->shouldReceive('send')->once();
        $bar = new ProgressBar($messenger, 5);
        
        $bar->advance(6);
        
        $this->assertEquals(5, $bar->getProgress());
    }
    
    public function testProgressPercent()
    {
        $messenger = $this->mock(Messenger::class);
        $messenger->shouldReceive('send')->once();
        $bar = new ProgressBar($messenger, 10);
        
        $bar->advance(2);
        
        $this->assertEquals(2 / 10.0, $bar->getProgressPercent());
    }

    public function testProgressPercentNoMax()
    {
        $messenger = $this->mock(Messenger::class);
        $bar = new ProgressBar($messenger);
        
        $this->assertEquals(0, $bar->getProgressPercent());
    }

    public function testStartResetsStepTo0()
    {
        $messenger = $this->mock(Messenger::class);
        $messenger->shouldReceive('send');
        $bar = new ProgressBar($messenger);
        
        $bar->advance(2);
        $bar->start();
        
        $this->assertEquals(0, $bar->getProgress());
    }

    public function testChangeMax()
    {
        $messenger = $this->mock(Messenger::class);
        $messenger->shouldReceive('send');
        $bar = new ProgressBar($messenger, 2);
        
        $bar->start(4);
        
        $this->assertEquals(4, $bar->getMaxSteps());

    }
    
    public function testFinishSendsComplete()
    {
        $messenger = $this->mock(Messenger::class);
        $messenger->shouldReceive('send')
            ->with(Mockery::on(function($message) {
                return $message->message == '{"title":"","step":0,"max":null,"complete":true}';
            }))
            ->once();
        $bar = new ProgressBar($messenger);
        
        $bar->finish();
    }    
}

<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Session;
use Voronoi\Apprentice\Artisan\CommandHelper;
use Voronoi\Apprentice\Artisan\ProgressBar;

class CommandHelperTest extends TestCase
{
	use RefreshDatabase;

	public function testCreateProgressBar()
	{
		$session = $this->mock(Session::class);
		$session->shouldReceive('runningViaApprentice')
		->andReturn(true);

		$session->shouldReceive('getOutputMessenger')
				->once()
				->andReturn(null);
		
		$commandHelper = new CommandHelper($session);
		
		$progressBar = $commandHelper->createProgressBar('Title');
		
		$this->assertTrue($progressBar instanceof ProgressBar);
	}
}

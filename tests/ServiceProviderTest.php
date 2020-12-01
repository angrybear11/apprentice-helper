<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Facades\Apprentice;

class ServiceProviderTest extends TestCase
{
	use RefreshDatabase;

	public function testRetrieveCommandHelper()
	{
		$helper = app()->make('apprentice');
		$this->assertNotNull($helper);
		
		// call a method on the facade to trigger getFacadeAccessor
		Apprentice::createProgressBar('test');
	}
}

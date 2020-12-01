<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\Libraries\PackageReader;

class PackageReaderTest extends TestCase
{
    public function testRetrievesVersion()
    {
        $reader = new PackageReader;
        $reader->setPath(__DIR__.'/test_composer.json');

        $this->assertEquals("1.0.0", $reader->version());
    }
}

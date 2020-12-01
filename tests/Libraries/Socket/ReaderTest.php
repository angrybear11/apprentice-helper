<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\Libraries\Socket\Reader;
use Voronoi\Apprentice\Exceptions\TimeoutException;
use Config;
use Exception;
use Storage;

class ReaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::disk('local')->makeDirectory('apprentice');

        $path = storage_path("app/apprentice") . '/A-1';
        $handle = fopen($path, 'w');
        fwrite($handle, 'test');
        fclose($handle);
    }

    protected function tearDown(): void
    {
        $path = storage_path("app/apprentice") . '/A-1';
        if (file_exists($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function testInvalidType()
    {
        try {
            new Reader('invalid*', 1);
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('Invalid type', $e->getMessage());
        }
    }

    public function testInvalidTypeLength()
    {
        try {
            $type = str_repeat('A', 201);
            new Reader($type, 1);
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('Type is too long', $e->getMessage());
        }
    }

    public function testInvalidId()
    {
        try {
            new Reader('A', 'A');
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('Invalid id', $e->getMessage());
        }
    }

    public function testInvalidIdLength()
    {
        try {
            $id = (Int)str_repeat('1', 13);
            new Reader('A', $id);
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('ID is too long', $e->getMessage());
        }
    }

    public function testFetchData()
    {
        $reader = new Reader('A', 1);
        $data = $reader->readLine();
        $this->assertEquals('test', $data);
    }

    public function testTimeout()
    {
        Config::set('apprentice.timeout', 0);

        try {
            $reader = new Reader('B', 1);
            $reader->readUntilData(0);
            $this->assertTrue(false, "shouldn't execute");
        } catch (TimeoutException $e) {
            $this->assertEquals('Read timeout', $e->getMessage());
        }
    }

    public function testRetrieveData()
    {
        $reader = new Reader('A', 1);
        $data = $reader->readUntilData(0);

        $this->assertEquals('test', $data);
    }

    public function testDeleteRemovesFile()
    {
        $reader = new Reader('A', 1);
        $data = $reader->deleteFile();

        $path = storage_path("app/apprentice") . '/A-1';
        $this->assertFalse(file_exists($path));
    }
}

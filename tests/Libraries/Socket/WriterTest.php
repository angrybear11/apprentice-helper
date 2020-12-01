<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\Libraries\Socket\Writer;
use Exception;

class WriterTest extends TestCase
{
    public function testInvalidType()
    {
        try {
            new Writer('invalid*', 1);
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('Invalid type', $e->getMessage());
        }
    }

    public function testInvalidTypeLength()
    {
        try {
            $type = str_repeat('A', 201);
            new Writer($type, 1);
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('Type is too long', $e->getMessage());
        }
    }

    public function testInvalidId()
    {
        try {
            new Writer('A', 'A');
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('Invalid id', $e->getMessage());
        }
    }

    public function testInvalidIdLength()
    {
        try {
            $id = (Int)str_repeat('1', 13);
            new Writer('A', $id);
            $this->assertTrue(false, "shouldn't execute");
        } catch (Exception $e) {
            $this->assertEquals('ID is too long', $e->getMessage());
        }
    }

    public function testWriteAddsData()
    {
        $path = storage_path("app/apprentice") . '/A-1';
        $handle = fopen($path, 'w');
        fwrite($handle, 'Hello');
        fclose($handle);

        $writer = new Writer('A', 1);
        $writer->write(' World!');

        $handle = fopen($path, 'r');
        $this->assertEquals("Hello World!", fgets($handle));
        fclose($handle);

        unlink($path);
    }
}

<?php

namespace Voronoi\Apprentice\Libraries\Socket;

use Exception;
use Storage;

class Writer
{
    public $handle;

    public function __construct($type, $id)
    {
        if (!preg_match("/^[a-zA-Z]+$/", $type)) {
            throw new Exception("Invalid type");
        }

        if (strlen($type) > 200) {
            throw new Exception("Type is too long");
        }

        if (!is_int($id)) {
            throw new Exception("Invalid id");
        }

        if (strlen((string)$id) > 12) {
            throw new Exception("ID is too long");
        }

        $filePath = storage_path("app/apprentice/$type-$id");
        Storage::disk('local')->makeDirectory('apprentice');
        $this->handle = fopen($filePath, 'a');
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    public function write($data)
    {
        return fwrite($this->handle, $data);
    }
}

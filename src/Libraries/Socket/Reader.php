<?php

namespace Voronoi\Apprentice\Libraries\Socket;

use File;
use Voronoi\Apprentice\Exceptions\TimeoutException;
use Exception;
use Storage;

class Reader
{
    protected $handle;
    protected $filePath;
    protected $bytesRead = 0;

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

        $path = storage_path("app/apprentice");
        Storage::disk('local')->makeDirectory('apprentice');
        $this->filePath = "$path/$type-$id";

        // create the file just in case it doesn't exist
        $this->handle = fopen($this->filePath, 'a');
        fclose($this->handle);

        $this->handle = fopen($this->filePath, 'r');
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    public function readLine()
    {
        // hack to force eof to reset. We move to the last seeked position.
        fseek($this->handle, $this->bytesRead);

        $data = fgets($this->handle);
        $this->bytesRead += strlen($data);

        return $data;
    }

    public function readUntilData($sleepDuration = 1)
    {
        $start = time();
        $timeoutAfter = (Int)config('apprentice.timeout', 300);
        $data = "";
        while (true) {
            $data = $this->readLine();
            $timeout = (time() - $start) > $timeoutAfter;
            if ($timeout) {
                throw new TimeoutException('Read timeout');
            }
            if (!$data && !$timeout) {
                sleep($sleepDuration);
            } else {
                break;
            }
        }
        return $data;
    }

    public function deleteFile()
    {
        unlink($this->filePath);
    }
}

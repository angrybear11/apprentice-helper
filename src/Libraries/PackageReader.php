<?php

namespace Voronoi\Apprentice\Libraries;

/// Reads composer package details
class PackageReader
{
    protected $path = __DIR__.'/../../composer.json';
    protected $contents;

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function version()
    {
        $contents = file_get_contents($this->path);
        $json = json_decode($contents, true);
        return $json["version"] ?? null;
    }
}

<?php

namespace Voronoi\Apprentice;

use Artisan;

class CommandProvider
{
    public function allowedCommands()
    {
        return Artisan::all();
    }
}

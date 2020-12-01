<?php

namespace Voronoi\Apprentice\Facades;

use Illuminate\Support\Facades\Facade;

class Apprentice extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'apprentice';
    }
}

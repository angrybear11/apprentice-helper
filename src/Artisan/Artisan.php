<?php

namespace Voronoi\Apprentice\Artisan;

use Symfony\Component\Console\Input\StringInput;
use Artisan as ArtisanLaravel;
use Voronoi\Apprentice\Message;

class Artisan
{
    public function call($command, $reader, $writer, $messenger)
    {
        $input  = new StringInput($command);
        $output = new FileStreamOutput($messenger);
        app()->bind("Illuminate\Console\OutputStyle", function ($app) use ($reader, $messenger, $input, $output) {
            return new OutputStyle($reader, $messenger, $input, $output);
        });

        ArtisanLaravel::handle(
            $input,
            $output
        );
        $messenger->send(new Message("done"));
    }
}

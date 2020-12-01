<?php

namespace Voronoi\Apprentice\Console\Commands;

use Illuminate\Console\Command;
use Artisan;
use Voronoi\Apprentice\CommandProvider;
use Voronoi\Apprentice\Libraries\PackageReader;
use Voronoi\Apprentice\Transformers\Command as CommandTransformer;

class Info extends Command
{
    protected $signature = 'apprentice:info';

    protected $description = 'Lists commands accessible to an Apprentice user';

    protected $commandProvider;
    protected $packageReader;
    protected $transformer;

    public function __construct(CommandProvider $commandProvider, PackageReader $packageReader, CommandTransformer $transformer)
    {
        $this->commandProvider = $commandProvider;
        $this->packageReader = $packageReader;
        $this->transformer = $transformer;
        parent::__construct();
    }

    public function handle()
    {
        $commands = $this->commandProvider->allowedCommands();

        if (count($commands) == 0) {
            $this->info("No commands");
            return;
        }

        $data = json_encode([
          'version'  => $this->packageReader->version(),
          'commands' => $this->transformer->transformCollection($commands),
        ]);
        $this->info($data);
    }
}

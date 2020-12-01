<?php

namespace Voronoi\Apprentice\Artisan;

use Voronoi\Apprentice\Session;

/**
 * Extra command methods for working with Apprentice. Use the built-in facade e.g. Apprentice::createProgressBar(...)
 */
class CommandHelper
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function createProgressBar($title, $max = null)
    {
        if (!$this->session->runningViaApprentice()) {
            return;
        }

        $messenger = $this->session->getOutputMessenger();

        return new ProgressBar($messenger, $max, $title);
    }
}

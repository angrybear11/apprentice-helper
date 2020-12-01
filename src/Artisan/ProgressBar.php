<?php

namespace Voronoi\Apprentice\Artisan;

use Symfony\Component\Console\Helper\ProgressBar as SymfonyProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Voronoi\Apprentice\Message;

class ProgressBar
{
    private $complete = false;
    private $step = 0;
    private $max = null;
    private $title = "";
    private $messenger;

    public function __construct($messenger, $max = null, $title = "")
    {
        $this->messenger = $messenger;
        if (null !== $max) {
            $this->max = max(0, (int) $max);
        }
        $this->title = $title;
    }
    
    public function setTitle(string $title)
    {
        $this->title = $title;
    }
    
    public function getTitle()
    {
        return $this->title;
    }

    public function getMaxSteps()
    {
        return $this->max;
    }

    public function getProgress(): int
    {
        return $this->step;
    }

    public function getProgressPercent(): float
    {
        return $this->max ? (float) $this->step / $this->max : 0;
    }

    public function advance($step)
    {
        $this->setProgress($this->step + $step);
    }

    public function setProgress($step)
    {
        $this->step = $step;

        if ($this->max && $step > $this->max) {
            $this->step = $this->max;
        }
        $this->display();
    }

    public function start($max = null)
    {
        $this->step = 0;

        if (null !== $max) {
            $this->max = max(0, (int) $max);
        }

        $this->display();
    }

    public function finish()
    {
        $this->complete = true;
        $this->display();
    }

    public function display()
    {
        $data = json_encode($this->toArray());
        $message = new Message('progress', $data);
        $this->messenger->send($message);
    }


    public function toArray()
    {
        return [
          'title'    => $this->title,
          'step'     => $this->step,
          'max'      => $this->max ? $this->max : null,
          'complete' => $this->complete,
        ];
    }
}

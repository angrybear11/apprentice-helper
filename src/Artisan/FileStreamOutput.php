<?php

namespace Voronoi\Apprentice\Artisan;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

use Voronoi\Apprentice\Message;

/**
 * StreamOutput writes the output to a given stream.
 *
 * Usage:
 *
 *     $output = new StreamOutput(fopen('php://stdout', 'w'));
 *
 * As `StreamOutput` can use any stream, you can also use a file:
 *
 *     $output = new StreamOutput(fopen('/path/to/output.log', 'a', false));
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FileStreamOutput extends Output
{
    private $messenger;

    public function __construct($messenger, int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, OutputFormatterInterface $formatter = null)
    {
        $this->messenger = $messenger;
        parent::__construct($verbosity, $decorated, $formatter);
    }

    protected function doWrite($message, $newline)
    {
        $output = $message;
        if ($newline) {
            $output = $message . "\n";
        }
        $data = [
          'messages' => [$output]
        ];
        $this->messenger->send(new Message("text", $data));
    }
}

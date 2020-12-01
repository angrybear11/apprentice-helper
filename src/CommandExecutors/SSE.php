<?php

namespace Voronoi\Apprentice\CommandExecutors;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Voronoi\Apprentice\Artisan\Artisan;
use Voronoi\Apprentice\Libraries\Socket\Writer;
use Voronoi\Apprentice\Libraries\Socket\Reader;
use Voronoi\Apprentice\Messenger;
use Voronoi\Apprentice\Message;
use Voronoi\Apprentice\Exceptions\TimeoutException;

// server side event
class SSE
{
    protected $artisan;

    public function __construct(Artisan $artisan)
    {
        $this->artisan = $artisan;
    }

    public function execute($command, $id)
    {
        $reader    = app()->makeWith(Reader::class, ['type' => 'interactive', 'id' => $id]);
        $writer    = app()->makeWith(Writer::class, ['type' => 'command', 'id' => $id]);
        $messenger = app()->makeWith(Messenger::class, ['writer' => $writer]);

        $this->artisan->call($command, $reader, $writer, $messenger);

        $reader->deleteFile();
    }

    public function commandInput($data, $id)
    {
        $writer = app()->makeWith(Writer::class, ['type' => 'interactive', 'id' => $id]);
        $writer->write($data);
    }

    public function outputResponse($id)
    {
        $response = new StreamedResponse($this->generateResponseHandler($id));
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cach-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        return $response;
    }

    private function generateResponseHandler($id)
    {
        return function () use ($id) {
            $reader  = app()->makeWith(Reader::class, ['type' => 'command', 'id' => $id]);
            $timeout = false;
            $name = "";
            do {
                $lastSuccessfulRead = time();
                try {
                    $data = $reader->readUntilData(1);
                    // Send using SSE format
                    echo "data: $data\n";
                    ob_flush();
                    flush();

                    $decoded = json_decode($data, true);
                    if (key_exists('type', $decoded)) {
                        $name = $decoded['type'];
                    }
                } catch (TimeoutException $exception) {
                    $messenger = new Messenger(null);
                    $data = $messenger->prepare(new Message('timeout', 'failed to receive output'));
                    echo "data: $data\n\n";
                    ob_flush();
                    flush();

                    $timeout = true;
                }
            } while ($name != "done" && !$timeout);

            $reader->deleteFile();
            session()->flush();
        };
    }
}

<?php

namespace Voronoi\Apprentice;

class Messenger
{
    public function __construct($writer)
    {
        $this->writer = $writer;
    }

    public function send(Message $message)
    {
        $data = $this->prepare($message);
        $this->writer->write($data . "\n");
    }

    public function prepare(Message $message)
    {
        return json_encode([
          "type" => $message->name,
          "data" => $message->message,
        ]);
    }
}

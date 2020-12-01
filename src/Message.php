<?php

namespace Voronoi\Apprentice;

class Message {

  public $name;
  public $message;

  function __construct($name, $message = "") {
    $this->name    = $name;
    $this->message = $message;
  }
}

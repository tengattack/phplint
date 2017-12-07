<?php

class Result {
  public $filePath;
  public $messages;

  function __construct(string $filePath, array $messages) {
    $this->filePath = $filePath;
    $this->messages = $messages;
  }

}

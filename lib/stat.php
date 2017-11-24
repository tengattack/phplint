<?php

class Stat {
  public $ruleId;
  public $severity;
  public $node;
  public $loc;
  public $message;
  public $data;
  public $fix;

  function __construct(string $ruleId, int $severity, &$node, $loc, $message, $data, $fix) {
    $this->ruleId = $ruleId;
    $this->severity = $severity;
    $this->node = $node;
    $this->loc = $loc;
    $this->message = $message;
    $this->data = $data;
    $this->fix = $fix;
  }
}

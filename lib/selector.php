<?php

class Selector {

  public $isToken;
  public $isExit;
  public $isAnyType;
  public $name;

  public function parse(string $name) {
    preg_match('/^(.*)(Token|Keyword)$/', $name, $matches);
    if (!empty($matches)) {
      $this->isToken = true;
      $this->isAnyType = !$matches[1];
    }
    preg_match('/^(.*)OnExit$/', $name, $matches);
    if (!empty($matches)) {
      $this->isExit = true;
      $this->name = $matches[1];
    } else {
      $this->isExit = false;
      $this->name = $name;
    }
    if (!$this->isToken) {
      $this->isAnyType = $this->name === 'Program';
    }
    return $this;
  }
}

<?php

class Formatter {

  public $type;
  public $reports;
  public $renderFn;

  function __construct(string $type, $reports) {
    $this->type = $type;
    $this->reports = $reports;
    $this->renderFn = require(ROOT . '/lib/formatters/' . $this->type . '.php');
  }

  static function exists(string $type) {
    return file_exists(ROOT . '/lib/formatters/' . $type . '.php');
  }

  function render() {
    $render = $this->renderFn;
    $results = array_map(function ($report) {
      return $report->getResult();
    }, $this->reports);
    return $render($results);
  }

}

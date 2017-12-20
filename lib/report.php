<?php

require_once ROOT . '/lib/result.php';

use Microsoft\PhpParser\{Node, Token};

class Report {

  private $filePath;
  private $sourceCode;
  private $stats;

  function __construct(string $filePath, &$sourceCode, &$stats) {
    $this->filePath = $filePath;
    $this->sourceCode = $sourceCode;
    $this->stats = $stats;
  }

  public function getMessages() {
    $messages = [];

    foreach ($this->stats as &$stat) {
      $sourceCode = '';
      if ($stat->node instanceof Node) {
        $sourceCode = $stat->node->getText();
      } else if ($stat->node instanceof Token) {
        $sourceCode = $this->sourceCode->getTokenText($stat->node);
      }

      $messages []= [
        'ruleId' => $stat->ruleId,
        'severity' => $stat->severity,
        'message' => $stat->message,
        'line' => $stat->loc['line'],
        'column' => $stat->loc['column'],
        'sourceCode' => $sourceCode,
      ];
    }

    return $messages;
  }

  public function getResult() {
    return new Result($this->filePath, $this->getMessages());
  }

}

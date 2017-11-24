<?php

use Microsoft\PhpParser\{Node, Token};

class Report {

  private $context;
  private $stats;

  function __construct(&$context, &$stats) {
    $this->context = $context;
    $this->stats = $stats;
  }

  function getMessages() {
    $messages = [];

    foreach ($this->stats as &$stat) {
      $sourceCode = '';
      if ($stat->node instanceof Node) {
        $sourceCode = $stat->node->getText();
      } else if ($stat->node instanceof Token) {
        $sourceCode = $stat->node->getText($this->context->astNode->fileContents);
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
}

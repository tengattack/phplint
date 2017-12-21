<?php

use Microsoft\PhpParser\TokenKind;

class NoExtraSemiRule extends Rule {

  public function filters() {
    return [
      'EmptyStatement',
      'CaseStatementNode',
    ];
  }

  public function EmptyStatement(&$node) {
    $this->report($node, 'Unnecessary semicolon.');
  }

  public function CaseStatementNode(&$node) {
    $idx = 0;
    foreach ($node->getChildTokens() as $token) {
      $idx++;
      if ($idx >= 2) {
        if ($token->kind === TokenKind::SemicolonToken) {
          $this->report($node, $token->start, "The colon token ':' is preferrable for case statement.");
        }
        break;
      }
    }
  }

}

return 'NoExtraSemiRule';

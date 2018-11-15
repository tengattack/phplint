<?php

use Microsoft\PhpParser\TokenKind;

class SwitchColonSpacingRule extends Rule {

  public function filters() {
    return [
      'CaseStatementNode',
    ];
  }

  public function CaseStatementNode(&$node) {
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::ColonToken) {
        if ($this->isSpaceBeforeToken($token, true)) {
          $this->report($node, $token->fullStart, 'Unexpected space(s) before this colon');
        }
        break;
      }
    }
  }

}

Rule::register(__FILE__, 'SwitchColonSpacingRule');

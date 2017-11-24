<?php

use Microsoft\PhpParser\TokenKind;

class NoArrayConstructorRule extends Rule {
  public function filters() {
    return [
      'ArrayCreationExpression',
    ];
  }

  public function ArrayCreationExpression(&$node) {
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::ArrayKeyword) {
        $this->report($node, "The array literal notation '[]' is preferrable.");
      }
    }
  }
}

return 'NoArrayConstructorRule';

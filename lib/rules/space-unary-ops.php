<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class SpaceUnaryOpsRule extends Rule {

  static $MESSAGE = 'Unexpected space after unary operator.';

  public function filters() {
    return [
      'UnaryOpExpression',
      'CastExpression',
    ];
  }

  public function check(&$node) {
    $child = $node->getChildNodes()->current();
    if ($child) {
      // the first node (only one)
      if ($this->isSpaceBeforeNode($child, true)) {
        $this->report($node, $child->getFullStart(), SpaceUnaryOpsRule::$MESSAGE);
      }
    }
  }

  public function UnaryOpExpression(&$node) {
    $this->check($node);
  }

  public function CastExpression(&$node) {
    $this->check($node);
  }

}

Rule::register(__FILE__, 'SpaceUnaryOpsRule');

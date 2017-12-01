<?php

use Microsoft\PhpParser\{Node, Token};

class SpaceInfixOpsRule extends Rule {

  static $MESSAGE = 'Infix operators must be spaced.';

  public function filters() {
    return [
      'BinaryExpression',
      'TernaryExpression',
      'AssignmentExpression',
      'ArrayElement',
    ];
  }

  public function check(&$node) {
    $start = false;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        $start = true;
        if (!$this->isSpaceBeforeToken($child, true)) {
          $this->report($node, $child->fullStart, SpaceInfixOpsRule::$MESSAGE);
        }
      } else if ($child instanceof Node) {
        if (!$start) {
          continue;
        }
        if (!$this->isSpaceBeforeNode($child, true)) {
          $this->report($node, $child->getFullStart(), SpaceInfixOpsRule::$MESSAGE);
        }
      }
    }
  }

  public function BinaryExpression(&$node) {
    return $this->check($node);
  }

  public function TernaryExpression(&$node) {
    return $this->check($node);
  }

  public function AssignmentExpression(&$node) {
    return $this->check($node);
  }

  public function ArrayElement(&$node) {
    // only work for DoubleArrowToken
    return $this->check($node);
  }

}

return 'SpaceInfixOpsRule';

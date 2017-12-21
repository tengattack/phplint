<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class SpaceInfixOpsRule extends Rule {

  static $MESSAGE = 'Infix operators must be spaced.';

  public function filters() {
    return [
      'BinaryExpression',
      'TernaryExpression',
      'AssignmentExpression',
      'ArrayElement',
      'Parameter',
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
      } elseif ($child instanceof Node) {
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
    $start = false;
    $lastNode = null;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        $start = true;
        $hasSpace = $this->isSpaceBeforeToken($child, true);
        if ($child->kind === TokenKind::ColonToken && !$lastNode) {
          // $foo = $bar ?: 1;
          if ($hasSpace) {
            $this->report($node, $child->fullStart, 'Shorthand ternary operator should not have spaces inside.');
          }
        } elseif (!$hasSpace) {
          $this->report($node, $child->fullStart, SpaceInfixOpsRule::$MESSAGE);
        }
        $lastNode = null;
      } elseif ($child instanceof Node) {
        $lastNode = $child;
        if (!$start) {
          continue;
        }
        if (!$this->isSpaceBeforeNode($child, true)) {
          $this->report($node, $child->getFullStart(), SpaceInfixOpsRule::$MESSAGE);
        }
      }
    }
  }

  public function AssignmentExpression(&$node) {
    return $this->check($node);
  }

  public function ArrayElement(&$node) {
    // only work for DoubleArrowToken
    return $this->check($node);
  }

  public function Parameter(&$node) {
    $start = false;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        if (!$start && $child->kind !== TokenKind::VariableName) {
          // starts from variable name
          // skip keyword, splat operator and etc.
          continue;
        }
        if ($start && !$this->isSpaceBeforeToken($child, true)) {
          $this->report($node, $child->fullStart, SpaceInfixOpsRule::$MESSAGE);
        }
        $start = true;
      } elseif ($child instanceof Node) {
        if (!$start) {
          continue;
        }
        if (!$this->isSpaceBeforeNode($child, true)) {
          $this->report($node, $child->getFullStart(), SpaceInfixOpsRule::$MESSAGE);
        }
      }
    }
  }

}

return 'SpaceInfixOpsRule';

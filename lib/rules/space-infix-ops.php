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
      'ConstElement',
      'ForeachKey',
      'ForeachValue',
      'Parameter',
    ];
  }

  public function check(&$node, $ignoreStartAfter = false) {
    $start = false;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        if ($ignoreStartAfter && $child->kind === TokenKind::AmpersandToken) {
          if (!$start) {
            continue;
          }
          $start = false;
        } else {
          $start = true;
        }
        if (!$this->isSpaceBeforeToken($child, true)) {
          $this->report($node, $child->fullStart, SpaceInfixOpsRule::$MESSAGE);
        }
      } elseif ($child instanceof Node) {
        if (!$start) {
          continue;
        }
        if (!$this->isSpaceBeforeNode($child, true)) {
          $this->report($node, $child->getFullStartPosition(), SpaceInfixOpsRule::$MESSAGE);
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
          $this->report($node, $child->getFullStartPosition(), SpaceInfixOpsRule::$MESSAGE);
        }
      }
    }
  }

  public function AssignmentExpression(&$node) {
    return $this->check($node, true);
  }

  public function ArrayElement(&$node) {
    // works for DoubleArrowToken
    return $this->check($node, true);
  }

  public function ConstElement(&$node) {
    return $this->check($node, true);
  }

  public function ForeachKey(&$node) {
    return $this->check($node, true);
  }

  public function ForeachValue(&$node) {
    if (!$this->isSpaceBeforeNode($node, true)) {
      $this->report($node, $node->getFullStartPosition(), SpaceInfixOpsRule::$MESSAGE);
    }
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
          $this->report($node, $child->getFullStartPosition(), SpaceInfixOpsRule::$MESSAGE);
        }
      }
    }
  }

}

Rule::register(__FILE__, 'SpaceInfixOpsRule');

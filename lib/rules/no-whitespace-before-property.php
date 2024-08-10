<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class NoWhitespaceBeforePropertyRule extends Rule {

  public function filters() {
    return [
      'MemberAccessExpression',
      'ScopedPropertyAccessExpression',
    ];
  }

  public function check(&$node) {
    $name = '';
    $index = 0;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($index <= 0) {
        $index++;
        continue;
      }
      if ($child instanceof Node) {
        $son = $child->getChildNodesAndTokens()->current();
        if ($son instanceof Node) {
          $name = $son->getText();
        } else if ($son instanceof Token) {
          if ($son->kind === TokenKind::VariableName) {
            $name = $this->getTokenText($son);
          }
        }
      } else if ($child instanceof Token) {
        if ($child->kind === TokenKind::Name) {
          $name = $this->getTokenText($child);
        }
      }
    }
    // reset index
    $index = 0;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($index <= 0) {
        $index++;
        continue;
      }
      if ($child instanceof Node) {
        if ($this->isSpaceBeforeNode($child)) {
          $this->report($node, $child->getFullStartPosition(), "Unexpected whitespace before property '$name'");
        }
      } else if ($child instanceof Token) {
        switch ($child->kind) {
        case TokenKind::ArrowToken:
        case TokenKind::ColonColonToken:
        case TokenKind::Name:
          if ($this->isSpaceBeforeToken($child)) {
            $this->report($node, $child->fullStart, "Unexpected whitespace before property '$name'");
          }
          break;
        }
      }
    }
  }

  public function MemberAccessExpression(&$node) {
    $this->check($node);
  }

  public function ScopedPropertyAccessExpression(&$node) {
    $this->check($node);
  }

}

Rule::register(__FILE__, 'NoWhitespaceBeforePropertyRule');

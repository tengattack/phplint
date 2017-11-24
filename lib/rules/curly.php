<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class CurlyRule extends Rule {

  private $multiOnly = false;

  protected function makeOptions() {
    $this->multiOnly = empty($this->options) ? false : $this->options[0] === 'multi';
  }

  public function filters() {
    return [
      'IfStatementNode',
      'ElseIfClauseNode',
      'ElseClauseNode',
      'WhileStatement',
      'DoStatement',
      'ForStatement',
      'ForeachStatement',
    ];
  }

  public function check(&$node, string $name) {
    $start = in_array($name, [ 'else', 'do' ]);
    $colon = false;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        switch ($child->kind) {
          case TokenKind::CloseParenToken:
            $start = true;
            break;
          case TokenKind::ColonToken:
            $colon = true;
            break;
        }
      } else if ($child instanceof Node) {
        if ($start) {
          switch ($child->getNodeKindName()) {
            case 'CompoundStatementNode':
              break;
            default:
              if ($this->isNewLineBeforeNode($child)) {
                if (!$colon) {
                  $this->report($node, $child->getFullStart(), "Expected { or : after '$name'.");
                }
              } else {
                // expression in same line
                if (!$this->multiOnly) {
                  $this->report($node, $child->getFullStart(), "Expected { or : after '$name'.");
                }
              }
          }
          break;
        } // if ($start)
      }
    }
  }

  public function IfStatementNode(&$node) {
    $this->check($node, 'if');
  }

  public function ElseIfClauseNode(&$node) {
    $this->check($node, 'elseif');
  }

  public function ElseClauseNode(&$node) {
    $this->check($node, 'else');
  }

  public function WhileStatement(&$node) {
    $this->check($node, 'while');
  }

  public function DoStatement(&$node) {
    $this->check($node, 'do');
  }

  public function ForStatement(&$node) {
    $this->check($node, 'for');
  }

  public function ForeachStatement(&$node) {
    $this->check($node, 'foreach');
  }

}

return 'CurlyRule';

<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class NoEmptyRule extends Rule {

  private $allowEmptyCatch = false;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (array_key_exists('allowEmptyCatch', $opt)) {
        $this->allowEmptyCatch = !!$opt['allowEmptyCatch'];
      }
    }
  }

  public function filters() {
    return [
      'CompoundStatementNode',
      'SwitchStatementNode',
    ];
  }

  public function CompoundStatementNode(&$node) {
    $hasChild = false;
    $closeBrace = null;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        if ($child->kind === TokenKind::CloseBraceToken) {
          $closeBrace = $child;
        }
      } elseif ($child instanceof Node) {
        $hasChild = true;
      }
    }
    if (!$hasChild) {
      if ($closeBrace) {
        // TODO: getLeadingComments
        $text = $closeBrace->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
        preg_match('/^\s*$/', $text, $matches);
        if (empty($matches)) {
          // has comments
          return;
        }
      }
      if ($this->allowEmptyCatch) {
        $parent = $this->context->parent();
        if ($parent && $parent->getNodeKindName() === 'CatchClause') {
          return;
        }
      }
      $this->report($node, 'Empty block statement.');
    }
  }

  public function SwitchStatementNode(&$node) {
    $hasChild = false;
    $brace = null;

    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        if ($child->kind === TokenKind::OpenBraceToken) {
          $brace = $child;
        }
      } elseif ($child instanceof Node) {
        if ($brace) {
          $hasChild = true;
          break;
        }
      }
    }

    if ($brace && !$hasChild) {
      $this->report($node, $brace->start, 'Empty switch statement.');
    }
  }

}

return 'NoEmptyRule';

<?php

use Microsoft\PhpParser\TokenKind;

class NoExtraSemiRule extends Rule {

  private $shortOpenTag = false;

  public function filters() {
    return [
      'EmptyStatement',
      'CaseStatementNode',
      'ExpressionStatement',
      'ScriptSectionStartTag',
      'ScriptSectionEndTag',
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

  public function ExpressionStatement(&$node) {
    if ($this->shortOpenTag) {
      $parent = $node->getParent();
      if (!$parent || $parent->getNodeKindName() === 'SourceFileNode') {
        // check whether the root node
        foreach ($node->getChildTokens() as $token) {
          if ($token->kind === TokenKind::SemicolonToken) {
            $this->report($node, $token->start, 'Unnecessary semicolon.');
          }
          break;
        }
      }
    }
  }

  public function ScriptSectionStartTag(&$token) {
    $this->shortOpenTag = $this->context->sourceCode->getTokenText($token) === '<?=';
  }

  public function ScriptSectionEndTag(&$token) {
    $this->shortOpenTag = false;
  }

}

return 'NoExtraSemiRule';

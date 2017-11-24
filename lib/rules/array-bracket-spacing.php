<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class ArrayBracketSpacingRule extends Rule {

  private $spaced;

  protected function makeOptions() {
    $this->spaced = empty($this->options)
                  ? false
                  : $this->options[0] === 'always';
  }

  public function filters() {
    return [
      'ArrayCreationExpression',
      'SubscriptExpression',
    ];
  }

  private function reportNoBeginningSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "There should be no space after '$tokenValue'.");
  }

  private function reportNoEndingSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "There should be no space before '$tokenValue'.");
  }

  private function reportRequiredBeginningSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "A space is required after '$tokenValue'.");
  }

  private function reportRequiredEndingSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "A space is required before '$tokenValue'.");
  }

  private function checkOpenToken(&$token, &$next) {
    if ($next instanceof Node) {
      $hasSpace = $this->isSpaceBeforeNode($next, $this->spaced);
    } else {
      $hasSpace = $this->isSpaceBeforeToken($next, $this->spaced);
    }
    if ($hasSpace && !$this->spaced) {
      $this->reportNoBeginningSpace($token);
    } elseif (!$hasSpace && $this->spaced) {
      $this->reportRequiredBeginningSpace($token);
    }
  }

  public function check(&$node) {
    $openToken = null;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Node) {
        if ($openToken) {
          $this->checkOpenToken($openToken, $child);
          $openToken = null;
        }
      } elseif ($child instanceof Token) {
        switch ($child->kind) {
        case TokenKind::OpenBracketToken:
          $openToken = $child;
          break;
        case TokenKind::CloseBracketToken:
          if ($openToken) {
            $this->checkOpenToken($openToken, $child);
            $openToken = null;
          }
          $hasSpace = $this->isSpaceBeforeToken($child, $this->spaced);
          if ($hasSpace && !$this->spaced) {
            $this->reportNoEndingSpace($child);
          } elseif (!$hasSpace && $this->spaced) {
            $this->reportRequiredEndingSpace($child);
          }
          break;
        }
      }
    }
  }

  public function ArrayCreationExpression(&$node) {
    $this->check($node);
  }

  public function SubscriptExpression(&$node) {
    $this->check($node);
  }
}

return 'ArrayBracketSpacingRule';

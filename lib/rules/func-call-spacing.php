<?php

use Microsoft\PhpParser\TokenKind;

class FuncCallSpacingRule extends Rule {

  private $spaced = false;

  protected function makeOptions() {
    $this->spaced = empty($this->options)
                  ? false
                  : $this->options[0] === 'always';
  }

  public function filters() {
    return [
      'CallExpression',
      'ObjectCreationExpression',
    ];
  }

  private function reportNoEndingSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "There should be no space before '$tokenValue'.");
  }

  private function reportRequiredEndingSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "A space is required before '$tokenValue'.");
  }

  private function check(&$node) {
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::OpenParenToken) {
        $hasSpace = $this->isSpaceBeforeToken($token, true);
        if ($hasSpace && !$this->spaced) {
          $this->reportNoEndingSpace($token);
        } elseif (!$hasSpace && $this->spaced) {
          $this->reportRequiredEndingSpace($token);
        }
      }
    }
  }

  public function CallExpression(&$node) {
    $this->check($node);
  }

  public function ObjectCreationExpression(&$node) {
    $this->check($node);
  }

}

return 'FuncCallSpacingRule';

<?php

use Microsoft\PhpParser\TokenKind;

class SemiSpacingRule extends Rule {

  private $spacedBefore = false;
  private $spacedAfter = true;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (array_key_exists('before', $opt)) {
        $this->spacedBefore = !!$opt['before'];
      }
      if (array_key_exists('after', $opt)) {
        $this->spacedAfter = !!$opt['after'];
      }
    }
  }

  public function filters() {
    return [
      'Program',
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

  public function check(&$node) {
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::SemicolonToken) {
        $hasSpace = $this->isSpaceBeforeToken($token, $this->spacedBefore);
        if ($hasSpace && !$this->spacedBefore) {
          $this->reportNoEndingSpace($token);
        } elseif (!$hasSpace && $this->spacedBefore) {
          $this->reportRequiredEndingSpace($token);
        }

        $nextToken = $this->getNextToken($node, $token);
        if ($nextToken) {
          // found next token
          $hasSpace = $this->isSpaceBeforeToken($nextToken, $this->spacedAfter);
          if ($hasSpace && !$this->spacedAfter) {
            $this->reportNoBeginningSpace($token);
          } elseif (!$hasSpace && $this->spacedAfter) {
            $this->reportRequiredBeginningSpace($token);
          }
        }
      }
    }
  }

  public function Program(&$node) {
    $this->check($node);
  }

}

return 'SemiSpacingRule';

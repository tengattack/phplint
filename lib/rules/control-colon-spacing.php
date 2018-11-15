<?php

use Microsoft\PhpParser\TokenKind;

class ControlColonSpacingRule extends Rule {

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
    // http://php.net/manual/en/control-structures.alternative-syntax.php
    // PHP offers an alternative syntax for some of its control structures;
    // namely, if, while, for, foreach, and switch.
    return [
      'IfStatementNode',
      'WhileStatement',
      'ForStatement',
      'ForeachStatement',
      'SwitchStatementNode',
      'DeclareStatement',
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
      if ($token->kind === TokenKind::ColonToken) {
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
        // only check one colon (must be last one)
        break;
      }
    }
  }

  public function IfStatementNode(&$node) {
    $this->check($node);
  }

  public function WhileStatement(&$node) {
    $this->check($node);
  }

  public function ForStatement(&$node) {
    $this->check($node);
  }

  public function ForeachStatement(&$node) {
    $this->check($node);
  }

  public function SwitchStatementNode(&$node) {
    $this->check($node);
  }

  public function DeclareStatement(&$node) {
    $this->check($node);
  }

}

Rule::register(__FILE__, 'ControlColonSpacingRule');

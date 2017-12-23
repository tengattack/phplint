<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class CommaSpacingRule extends Rule {

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
      'ArrayElementList',
      'ParameterDeclarationList',
      'ArgumentExpressionList',
      'UseVariableNameList',
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
    $prevToken = null;
    $passNode = false;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Node) {
        $passNode = true;
        if (!$prevToken) {
          continue;
        }
        $hasSpace = $this->isSpaceBeforeNode($child, $this->spacedAfter);
        if ($hasSpace && !$this->spacedAfter) {
          $this->reportNoBeginningSpace($prevToken);
        } elseif (!$hasSpace && $this->spacedAfter) {
          $this->reportRequiredBeginningSpace($prevToken);
        }
        $prevToken = null;
      } else if ($child instanceof Token) {
        $prevToken = $child;
        if (!$passNode) {
          continue;
        }
        $hasSpace = $this->isSpaceBeforeToken($child, $this->spacedBefore);
        if ($hasSpace && !$this->spacedBefore) {
          $this->reportNoEndingSpace($child);
        } elseif (!$hasSpace && $this->spacedBefore) {
          $this->reportRequiredEndingSpace($child);
        }
        $passNode = false;
      }
    }
  }

  public function ArrayElementList(&$node) {
    $this->check($node);
  }

  public function ParameterDeclarationList(&$node) {
    $this->check($node);
  }

  public function ArgumentExpressionList(&$node) {
    $this->check($node);
  }

  public function UseVariableNameList(&$node) {
    $this->check($node);
  }

}

return 'CommaSpacingRule';

<?php

use Microsoft\PhpParser\{Node, Token};

class SpaceInParensRule extends Rule {

  static $REQUIRED_SPACE_MESSAGE = "A space is required inside this paren.";
  static $REJECTED_SPACE_MESSAGE = "There should be no spaces inside this paren.";

  private $spaced;

  protected function makeOptions() {
    $this->spaced = empty($this->options)
                  ? false
                  : $this->options[0] === 'always';
  }

  public function filters() {
    return [
      'OpenParenToken',
      'CloseParenToken',
    ];
  }

  public function OpenParenToken(&$token) {
    $node = $this->context->current();
    $nextToken = $this->getNextToken($node, $token);
    if (!$nextToken) {
      return;
    }

    $hasSpace = $this->isSpaceBeforeToken($nextToken, $this->spaced);
    if ($hasSpace && !$this->spaced) {
      $this->report($token, $token->fullStart, SpaceInParensRule::$REJECTED_SPACE_MESSAGE);
    } elseif (!$hasSpace && $this->spaced) {
      $this->report($token, $token->fullStart, SpaceInParensRule::$REQUIRED_SPACE_MESSAGE);
    }
  }

  public function CloseParenToken(&$token) {
    $hasSpace = $this->isSpaceBeforeToken($token, $this->spaced);
    if ($hasSpace && !$this->spaced) {
      $this->report($token, $token->fullStart, SpaceInParensRule::$REJECTED_SPACE_MESSAGE);
    } elseif (!$hasSpace && $this->spaced) {
      $this->report($token, $token->fullStart, SpaceInParensRule::$REQUIRED_SPACE_MESSAGE);
    }
  }

}

Rule::register(__FILE__, 'SpaceInParensRule');

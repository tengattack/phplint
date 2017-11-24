<?php

use Microsoft\PhpParser\{Node, Token};

class SpaceInParensRule extends Rule {

  static $REJECTED_SPACE_MESSAGE = "There should be no spaces inside this paren.";

  public function filters() {
    return [
      'OpenParenToken',
      'CloseParenToken',
    ];
  }

  public function OpenParenToken(&$token) {
    // TODO: find next walking in parent
    $node = $this->context->current();
    $position = $token->getEndPosition();

    $nextChild = null;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child->getFullStart() >= $position) {
        $nextChild = $child;
        break;
      }
    }

    if ($nextChild instanceof Node) {
      if ($this->isSpaceBeforeNode($nextChild)) {
        $this->report($token, $token->fullStart, SpaceInParensRule::$REJECTED_SPACE_MESSAGE);
      }
    } else if ($nextChild instanceof Token) {
      if ($this->isSpaceBeforeToken($nextChild)) {
        $this->report($token, $token->fullStart, SpaceInParensRule::$REJECTED_SPACE_MESSAGE);
      }
    }
  }

  public function CloseParenToken(&$token) {
    if ($this->isSpaceBeforeToken($token)) {
      $this->report($token, $token->fullStart, SpaceInParensRule::$REJECTED_SPACE_MESSAGE);
    }
  }

}

return 'SpaceInParensRule';

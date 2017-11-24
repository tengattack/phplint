<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class EqeqeqRule extends Rule {

  public function filters() {
    return [
      'EqualsEqualsToken',
      'ExclamationEqualsToken',
    ];
  }

  private function check(&$token) {
    $actualOperator = $this->getTokenText($token);
    $expectedOperator = '';
    switch ($token->kind) {
      case TokenKind::EqualsEqualsToken:
        $expectedOperator = '===';
        break;
      case TokenKind::ExclamationEqualsToken:
        $expectedOperator = '!==';
        break;
    }
    $this->report($token, "Expected '$expectedOperator' and instead saw '$actualOperator'");
  }

  public function EqualsEqualsToken(&$token) {
    $this->check($token);
  }

  public function ExclamationEqualsToken(&$token) {
    $this->check($token);
  }

}

return 'EqeqeqRule';

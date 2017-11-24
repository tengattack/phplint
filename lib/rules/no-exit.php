<?php

class NoExitRule extends Rule {

  public function filters() {
    return [
      'ExitIntrinsicExpression',
    ];
  }

  public function ExitIntrinsicExpression(&$node) {
    $this->report($node, 'Not allow exit/die call');
  }

}

return 'NoExitRule';

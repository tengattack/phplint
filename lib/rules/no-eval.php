<?php

class NoEvalRule extends Rule {

  public function filters() {
    return [
      'EvalIntrinsicExpression',
    ];
  }

  public function EvalIntrinsicExpression(&$node) {
    $this->report($node, 'Not allow eval call');
  }

}

Rule::register(__FILE__, 'NoEvalRule');

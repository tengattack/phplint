<?php

class NoExtraSemiRule extends Rule {

  public function filters() {
    return [
      'EmptyStatement',
    ];
  }

  public function EmptyStatement(&$node) {
    $this->report($node, 'Unnecessary semicolon.');
  }

}

return 'NoExtraSemiRule';

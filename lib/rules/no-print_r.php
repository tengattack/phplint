<?php

class NoPrintRRule extends Rule {

  public function filters() {
    return [
      'CallExpression',
    ];
  }

  public function CallExpression(&$node) {
    foreach ($node->getChildNodes() as $child) {
      if ($child->getNodeKindName() === 'QualifiedName') {
        if ($child->getText() === 'print_r') {
          $this->report($node, 'Not allow print_r call');
        }
      }
      break;
    }
  }

}

return 'NoPrintRRule';

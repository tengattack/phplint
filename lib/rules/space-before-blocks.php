<?php

use Microsoft\PhpParser\TokenKind;

class SpaceBeforeBlocksRule extends Rule {

  private $spaced;

  protected function makeOptions() {
    $this->spaced = empty($this->options)
                  ? false
                  : $this->options[0] === 'always';
  }

  public function filters() {
    return [
      'CompoundStatementNode',
    ];
  }

  private function reportNoEndingSpace(&$node) {
    $this->report($node, $node->getFullStartPosition(), 'There should be no space before blocks.');
  }

  private function reportRequiredEndingSpace(&$node) {
    $this->report($node, $node->getFullStartPosition(), 'A space is required before blocks.');
  }

  public function CompoundStatementNode(&$node) {
    $hasSpace = $this->isSpaceBeforeNode($node, $this->spaced);
    if ($hasSpace && !$this->spaced) {
      $this->reportNoEndingSpace($node);
    } elseif (!$hasSpace && $this->spaced) {
      $this->reportRequiredEndingSpace($node);
    }
  }

}

Rule::register(__FILE__, 'SpaceBeforeBlocksRule');

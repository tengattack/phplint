<?php

class NoLonelyIfRule extends Rule {

  public function filters() {
    return [
      'ElseClauseNode',
    ];
  }

  private function countExtraNode(&$node, &$nodeIf): int {
    $extraNodes = 0;
    $ifNodes = 0;
    foreach ($node->getChildNodes() as $child) {
      if ($extraNodes > 1) {
        break;
      }
      switch ($child->getNodeKindName()) {
        case 'IfStatementNode':
          $nodeIf = $child;
          if ($ifNodes > 0) {
            $extraNodes++;
          }
          $ifNodes++;
          break;
        case 'CompoundStatementNode':
          // walk into
          $extraNodes += $this->countExtraNode($child, $nodeIf);
          break;
        default:
          $extraNodes++;
      }
    }
    return $extraNodes;
  }

  public function ElseClauseNode(&$node) {
    $nodeIf = null;
    if (!$this->countExtraNode($node, $nodeIf) && $nodeIf) {
      $this->report($nodeIf, 'Unexpected if as the only statement in an else block.');
    }
  }

}

Rule::register(__FILE__, 'NoLonelyIfRule');

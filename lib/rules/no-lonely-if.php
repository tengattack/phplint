<?php

class NoLonelyIfRule extends Rule {

  public function filters() {
    return [
      'ElseClauseNode',
    ];
  }

  private function countExtraNode(&$node, &$nodeIf): int {
    $extraNode = 0;
    foreach ($node->getChildNodes() as $child) {
      if ($extraNode > 1) {
        break;
      }
      switch ($child->getNodeKindName()) {
        case 'IfStatementNode':
          $nodeIf = $child;
          break;
        case 'CompoundStatementNode':
          // walk into
          $extraNode += $this->countExtraNode($child, $nodeIf);
          break;
        default:
          $extraNode++;
      }
    }
    return $extraNode;
  }

  public function ElseClauseNode(&$node) {
    $nodeIf = null;
    if (!$this->countExtraNode($node, $nodeIf) && $nodeIf) {
      $this->report($nodeIf, 'Unexpected if as the only statement in an else block.');
    }
  }

}

return 'NoLonelyIfRule';

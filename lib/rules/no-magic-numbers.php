<?php

class NoMagicNumbersRule extends Rule {

  private $ignore = [];
  private $ignoreArrayIndexes = false;
  private $enforceConst = false;
  private $detectObjects = false;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (!is_array($opt)) {
        return;
      }
      if (array_key_exists('ignore', $opt) && is_array($opt['ignore'])) {
        $this->ignore = $opt['ignore'];
      }
      if (array_key_exists('ignoreArrayIndexes', $opt)) {
        $this->ignoreArrayIndexes = !!$opt['ignoreArrayIndexes'];
      }
      if (array_key_exists('enforceConst', $opt)) {
        $this->enforceConst = !!$opt['enforceConst'];
      }
      if (array_key_exists('detectObjects', $opt)) {
        $this->detectObjects = !!$opt['detectObjects'];
      }
    }
  }

  public function filters() {
    return [
      'NumericLiteral',
    ];
  }

  static function isConst($name) {
    return $name === strtoupper($name);
  }

  public function NumericLiteral(&$node) {
    $raw = $node->getText();
    $parent = $node->parent;
    $parentKind = $parent ? $parent->getNodeKindName() : null;
    if (!$this->ignoreArrayIndexes && $parentKind === 'SubscriptExpression') {
      $this->report($node, "No magic number: $raw.");
    }
    if (in_array((int)$raw, $this->ignore)) {
      return;
    }
    if ($this->enforceConst && $parentKind === 'AssignmentExpression') {
      $child = $parent->getChildNodes()->current();
      if ($child && $child->getNodeKindName() === 'Variable') {
        $name = $child->getText();
        if (!self::isConst($name)) {
          $this->report($node, 'Number constants declarations must use uppercase variable name.');
        }
      }
    } elseif ($this->detectObjects && $parentKind === 'ArrayElement') {
      $this->report($node, "No magic number: $raw.");
    } elseif ($parentKind === 'BinaryExpression' || $parentKind === 'Parameter' || !$parentKind) {
      $this->report($node, "No magic number: $raw.");
    }
  }

}

return 'NoMagicNumbersRule';

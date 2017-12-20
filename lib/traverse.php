<?php

use Microsoft\PhpParser\{Token, Node};

class Traverse {
  private $sourceCode = null;
  private $currentNode = null;
  private $parentNodes = [];
  private $depth = 0;
  private $context;

  function __construct(&$sourceCode) {
    $this->sourceCode = $sourceCode;
    $this->currentNode = null;
    $this->parentNodes = [];
    $this->depth = 0;
  }

  public function parent() {
    if (empty($this->parentNodes)) {
      return null;
    }
    return $this->parentNodes[0];
  }

  public function current() {
    return $this->currentNode;
  }

  public function enterNode(&$node) {
    array_unshift($this->parentNodes, $this->currentNode);
    $this->currentNode = $node;
    $this->depth++;
    $this->context->applyNodeSelectors($node, false);
  }

  public function leaveNode(&$node) {
    $this->context->applyNodeSelectors($node, true);
    $this->currentNode = array_shift($this->parentNodes);
    $this->depth--;
  }

  private function walkNode(&$node) {
    $this->enterNode($node);
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child instanceof Node) {
        if (defined('VERBOSE') && VERBOSE) {
          echo str_repeat(' ', $this->depth * 2)
            . 'Node ' . $child->getNodeKindName()
            . ' ' . json_encode($child->getFullText())
            . "\n";
        }
        $this->walkNode($child);
      } else {
        if (defined('VERBOSE') && VERBOSE) {
          echo str_repeat(' ', $this->depth * 2)
            . 'Token ' . Token::getTokenKindNameFromValue($child->kind)
            . ' ' . json_encode($this->sourceCode->getTokenText($child))
            . "\n";
        }
        $this->context->applyTokenSelectors($child);
      }
    }
    $this->leaveNode($node);
  }

  public function walk(&$context) {
    $this->context = $context;
    $this->context->setTraverse($this);
    $this->walkNode($this->sourceCode->astNode);
  }
}

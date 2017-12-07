<?php

require_once ROOT . '/lib/report.php';
require_once ROOT . '/lib/selector.php';
require_once ROOT . '/lib/stat.php';

use Microsoft\PhpParser\{Node, Token};

class Context {

  public $astNode;
  private $traverse;
  private $sourceLineOffsets;
  private $rules;
  private $ruleObjects;
  private $enterSelectorsByNodeType;
  private $exitSelectorsByNodeType;
  private $anyTypeEnterSelectors;
  private $anyTypeExitSelectors;
  private $tokenSelectors;
  private $anyTokenSelectors;
  public $stats = [];

  static function parseSelector(string $selectorName) {
    return (new Selector())->parse($selectorName);
  }

  function __construct(&$astNode) {
    $this->astNode = $astNode;
    unset($this->sourceLineOffsets);
  }

  public function setTraverse(&$traverse) {
    $this->traverse = $traverse;
  }

  public function loadRules($rules) {
    $this->ruleObjects = [];
    $this->selectors = [];
    foreach ($rules as $rule => $level) {
      $Rule = require(ROOT . '/lib/rules/' . $rule . '.php');
      $this->ruleObjects []= new $Rule($this, $rule, $level);
    }
    $this->rules = $rules;
    $this->flattenRules();
  }

  public function flattenRules() {
    $this->enterSelectorsByNodeType = [];
    $this->exitSelectorsByNodeType = [];
    $this->anyTypeEnterSelectors = [];
    $this->anyTypeExitSelectors = [];
    $this->tokenSelectors = [];
    $this->anyTokenSelectors = [];

    foreach ($this->ruleObjects as &$rule) {
      $filters = $rule->filters();
      foreach ($filters as $f) {
        $selector = Context::parseSelector($f);
        if ($selector->isAnyType && !$selector->isToken) {
          $selectorList = $selector->isExit
                        ? 'anyTypeExitSelectors'
                        : 'anyTypeEnterSelectors';
          $this->$selectorList []= &$rule;
        } else {
          $selectorList = $selector->isToken
                        ? ($selector->isAnyType
                            ? 'anyTokenSelectors'
                            : 'tokenSelectors')
                        : ($selector->isExit
                            ? 'exitSelectorsByNodeType'
                            : 'enterSelectorsByNodeType');
          if (array_key_exists($selector->name, $this->$selectorList)) {
            $this->$selectorList[$f] []= &$rule;
          } else {
            $this->$selectorList[$f] = [ &$rule ];
          }
        }
      }
    }
  }

  public function applyTokenSelectors(&$token) {
    $type = Token::getTokenKindNameFromValue($token->kind);
    if (array_key_exists($type, $this->tokenSelectors)) {
      $selectors = $this->tokenSelectors[$type];
      foreach ($selectors as &$rule) {
        $rule->$type($token);
      }
    }
    // any token
    $tokenType = Selector::getTokenType($type);
    if ($tokenType) {
      if (array_key_exists($tokenType, $this->anyTokenSelectors)) {
        $selectors = $this->anyTokenSelectors[$tokenType];
        foreach ($selectors as &$rule) {
          $rule->$tokenType($token);
        }
      }
    }
  }

  public function applyNodeSelectors(&$node, bool $isExit) {
    $type = $node->getNodeKindName();
    $selectorList = $isExit
                  ? 'exitSelectorsByNodeType'
                  : 'enterSelectorsByNodeType';
    if (array_key_exists($type, $this->$selectorList)) {
      $selectors = $this->$selectorList[$type];
      $selectorCall = $type . ($isExit ? 'OnExit' : '');
      foreach ($selectors as &$rule) {
        $rule->$selectorCall($node);
      }
    }
    $anyTypeSelectorList = $isExit
                         ? 'anyTypeExitSelectors'
                         : 'anyTypeEnterSelectors';
    $anyCall = 'Program' . ($isExit ? 'OnExit' : '');
    foreach ($this->$anyTypeSelectorList as &$rule) {
      $rule->$anyCall($node);
    }
  }

  public function positionToLocation(int $pos) {
    if (!isset($this->sourceLineOffsets)) {
      // generate source line offset caches
      $lines = explode("\n", $this->astNode->fileContents);
      $length = 0;
      $this->sourceLineOffsets = [ $length ];
      foreach ($lines as $line) {
        $length += strlen($line) + 1;
        $this->sourceLineOffsets []= $length;
      }
    }

    $line = 0;
    $column = 0;
    $length = 0;
    for ($i = 0; $i < count($this->sourceLineOffsets) - 1; $i++) {
      $offset = $this->sourceLineOffsets[$i];
      if ($pos >= $offset && $pos < $this->sourceLineOffsets[$i + 1]) {
        $line = $i + 1;
        $column = $pos - $offset + 1;
        break;
      }
    }
    return [ 'line' => $line, 'column' => $column ];
  }

  public function parent() {
    return $this->traverse->parent();
  }

  public function current() {
    return $this->traverse->current();
  }

  public function report(string $ruleId, int $severity, &$node, $pos, $message, $data, $fix) {
    if (gettype($pos) === 'string') {
      // [ &$node, $message, $data, $fix ]
      $fix = $data;
      $data = $message;
      $message = $pos;
      $pos = null;
    }
    if (is_null($pos)) {
      if ($node instanceof Node) {
        $pos = $node->getStart();
      } else if ($node instanceof Token) {
        $pos = $node->start;
      }
    }
    $loc = $this->positionToLocation($pos);
    $this->stats []= new Stat($ruleId, $severity, $node, $loc, $message, $data, $fix);
  }

  public function getReport() {
    return new Report($this, $this->stats);
  }

}

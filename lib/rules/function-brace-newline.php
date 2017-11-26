<?php

use Microsoft\PhpParser\TokenKind;

class FunctionBraceNewlineRule extends Rule {

  private $newline = [
    'anonymous' => false,
    'named' => true,
  ];

  protected function makeOptions() {
    if (!empty($this->options)) {
      if (is_bool($this->options[0])) {
        $val = $this->options[0];
        if (in_array($val, self::$STYLE_TYPES)) {
          $this->newline['anonymous'] = $val;
          $this->newline['named'] = $val;
        }
      } elseif (is_array($this->options[0])) {
        $style = $this->options[0];
        foreach ($style as $key => $val) {
          if (array_key_exists($key, $this->newline)) {
            $this->newline[$key] = !!$val;
          }
        }
      }
    }
  }

  public function filters() {
    return [
      'FunctionDeclaration',
      'MethodDeclaration',
      'AnonymousFunctionCreationExpression',
    ];
  }

  private function check(&$node, $type) {
    foreach ($node->getChildNodes() as $child) {
      if ($child->getNodeKindName() === 'CompoundStatementNode') {
        $text = $child->getLeadingCommentAndWhitespaceText();
        $hasNewLine = strpos($text, "\n") !== false;
        if ($hasNewLine && !$this->newline[$type]) {
          $this->report($child, $child->getFullStart(), "There should be no new line before '{'.");
        } elseif (!$hasNewLine && $this->newline[$type]) {
          $this->report($child, $child->getFullStart(), "A new line is required before '{'.");
        }
        break;
      }
    }
  }

  public function FunctionDeclaration(&$node) {
    $this->check($node, 'named');
  }

  public function MethodDeclaration(&$node) {
    $this->check($node, 'named');
  }

  public function AnonymousFunctionCreationExpression(&$node) {
    $this->check($node, 'anonymous');
  }

}

return 'FunctionBraceNewlineRule';

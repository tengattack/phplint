<?php

use Microsoft\PhpParser\TokenKind;

class CamelCaseRule extends Rule {

  private $camelCase = true;
  private $ignore = [];

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (is_string($opt)) {
          $this->camelCase = $opt === 'always';
          if (count($this->options) > 1) {
            $opt = $this->options[1];
          }
      }
      if (is_array($opt)) {
        if (array_key_exists('ignore', $opt)) {
          $this->ignore = $opt['ignore'];
        }
      }
    }
  }

  public function filters() {
    return [
      'Variable',
      'Parameter',
    ];
  }

  private function isUnderscored($name) {
    // if there's an underscore, it might be A_CONSTANT, which is okay
    return strpos($name, '_') !== false && $name !== strtoupper($name);
  }

  private function checkCamelCase(string $name) {
    // it might be A_CONSTANT, which is okay
    return $name !== strtolower($name)
      && $name !== strtoupper($name)
      && strpos($name, '_') === false;
  }

  private function checkNode(&$node) {
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::VariableName) {
        $name = $this->getTokenText($token);
        if ($name && $name[0] === '$') {
          $name = substr($name, 1);
        }
        if (in_array($name, $this->ignore)) {
          return;
        }
        /**
         * Strip leading and trailing underscores as there are commonly used to flag
         * private/protected identifiers
         */
        $name = preg_replace('/^_+|_+$/', '', $name);
        if ($this->camelCase) {
          // not allow underscore
          if ($this->isUnderscored($name)) {
            $this->report($node, "Identifier '$name' is not in camel case.");
          }
        } elseif ($this->checkCamelCase($name)) {
          // not allow camelcase
          $this->report($node, "Identifier '$name' is in camel case.");
        }
      }
    }
  }

  public function Parameter(&$node) {
    $this->checkNode($node);
  }

  public function Variable(&$node) {
    $this->checkNode($node);
  }
}

return 'CamelCaseRule';

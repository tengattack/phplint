<?php

use Microsoft\PhpParser\TokenKind;

class NoCamelCaseRule extends Rule {
  public function filters() {
    return [
      'Variable',
      'Parameter',
    ];
  }

  private function checkCamelCase(string $name) {
    /*
     * Strip leading and trailing underscores as there are commonly used to flag
     * private/protected identifiers
     */
    $name = preg_replace('/^_+|_+$/', '', $name);
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
        if ($this->checkCamelCase($name)) {
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

return 'NoCamelCaseRule';

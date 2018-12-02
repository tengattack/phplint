<?php

use Microsoft\PhpParser\TokenKind;

class QuotesRule extends Rule {

  private $quoteOption = 'single';
  private $avoidEscape = true;
  private $allowTemplateLiterals = true;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = null;
      if (is_string($this->options[0])) {
        if (!in_array($this->options[0], ['single', 'double'])) {
          throw new \Exception('Invalid options for quotes');
        }
        $this->quoteOption = $this->options[0];
        if (count($this->options) > 1 && is_array($this->options[1])) {
          $opt = $this->options[1];
        }
      } elseif (is_array($this->options[0])) {
        $opt = $this->options[0];
      }
      if ($opt) {
        if (key_exists('avoidEscape', $opt)) {
          $this->avoidEscape = $opt['avoidEscape'];
        }
        if (key_exists('allowTemplateLiterals', $opt)) {
          $this->allowTemplateLiterals = $opt['allowTemplateLiterals'];
        }
      }
    }
  }

  public function filters() {
    return [
      'StringLiteral',
    ];
  }

  protected function createErrorMessage(&$node, &$token, $quoteOption) {
    $this->report($node, $token->fullStart, "Strings must use ${quoteOption}quote.");
  }

  public function StringLiteral(&$node) {
    foreach ($node->getChildTokens() as $token) {
      switch ($token->kind) {
        case TokenKind::HeredocStart:
          // ignore heredoc string literal
          return;
        case TokenKind::StringLiteralToken:
          $text = $this->getTokenText($token);
          if ($text[0] === "'") {
            // singlequote
            if ($this->quoteOption === 'double') {
              if ($this->avoidEscape && strpos($text, '"') !== false) {
                break;
              }
              $this->createErrorMessage($node, $token, $this->quoteOption);
            }
          } else {
            // doublequote
            if ($this->quoteOption === 'single') {
              if ($this->avoidEscape && strpos($text, "'") !== false) {
                break;
              }
              $this->createErrorMessage($node, $token, $this->quoteOption);
            }
          }
          break;
        case TokenKind::DoubleQuoteToken:
          // this token only appear when it contains template literals
          if ($this->quoteOption === 'single' && !$this->allowTemplateLiterals) {
            $this->createErrorMessage($node, $token, $this->quoteOption);
          }
          return;
      }
    }
  }

}

Rule::register(__FILE__, 'QuotesRule');

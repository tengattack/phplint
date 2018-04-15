<?php

class EOLLastRule extends Rule {

  static $MISSING_MESSAGE = "Newline required at end of file but not found.";
  static $UNEXPECTED_MESSAGE = "Newline not allowed at end of file.";

  private $enforced;

  protected function makeOptions() {
    $this->enforced = empty($this->options)
                    ? false
                    : $this->options[0] === 'always';
  }

  public function filters() {
    return [
      'EndOfFileToken',
    ];
  }

  public function EndOfFileToken(&$token) {
    $text = $this->getTokenText($token);
    if ($text === '') {
      $node = $this->context->current();
      $prevToken = $this->getPreviousToken($node, $token);
      $text = $this->getTokenText($prevToken);
      $hasNewLine = substr($text, -1, 1) === "\n";
    } else {
      $hasNewLine = $this->isNewLineBeforeToken($token);
    }
    if ($hasNewLine && !$this->enforced) {
      $this->report($token, $token->fullStart, EOLLastRule::$UNEXPECTED_MESSAGE);
    } elseif (!$hasNewLine && $this->enforced) {
      $this->report($token, $token->fullStart, EOLLastRule::$MISSING_MESSAGE);
    }
  }

}

return 'EOLLastRule';

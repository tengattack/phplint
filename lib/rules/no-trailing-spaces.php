<?php

class NoTrailingSpacesRule extends Rule {

  static $BLANK_CLASS = '[ \t\x{00a0}\x{2000}-\x{200b}\x{3000}]';

  public function filters() {
    return [
      'ProgramOnExit',
    ];
  }

  public function ProgramOnExit(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);

      $offset = 0;
      $lastOffset = 0;
      while (true) {
        preg_match('/(' . self::$BLANK_CLASS . '+)\r?\n/um', $text, $matches, PREG_OFFSET_CAPTURE, $lastOffset);
        if (!empty($matches)) {
          $offset = $matches[0][1];
          $this->report($token, $token->fullStart + $offset, 'Trailing spaces not allowed.');
          $lastOffset = $offset + strlen($matches[0][0]);
        } else {
          break;
        }
      }
    }
  }

}

return 'NoTrailingSpacesRule';

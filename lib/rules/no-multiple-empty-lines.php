<?php

class NoMultipleEmptyLinesRule extends Rule {

  private $max = 2;

  protected function makeOptions() {
    if (!empty($this->options)) {
      if (is_array($this->options[0])) {
        $opts = $this->options[0];
        if (array_key_exists('max', $opts)) {
          $this->max = (int)$opts['max'];
          if ($this->max <= 0) {
            throw new \Exception('Invalid options for no-multi-empty-lines');
          }
        }
      }
    }
  }

  public function filters() {
    return [
      'Program',
    ];
  }

  public function Program(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);

      $offset = 0;
      $lastOffset = 0;
      while (true) {
        preg_match('/(\r?\n){'.($this->max + 1).',}/sU', $text, $matches, PREG_OFFSET_CAPTURE, $lastOffset);
        if (!empty($matches)) {
          $offset = $matches[0][1];
          $lastOffset = $offset + strlen($matches[0][0]);
          $this->report($token, $token->fullStart + $offset + 1, 'Unexcepted multiple empty lines.');
        } else {
          break;
        }
      }
    }
  }

}

return 'NoMultipleEmptyLinesRule';

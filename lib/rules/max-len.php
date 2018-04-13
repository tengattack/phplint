<?php

class MaxLenRule extends Rule {

  private $maxLength = 80;
  private $tabWidth = 4;
  private $lastLine = 0;

  protected function makeOptions() {
    if (!empty($this->options)) {
      if (is_numeric($this->options[0])) {
        $this->maxLength = (int)$this->options[0];
      } elseif (is_array($this->options[0])) {
        $opt = $this->options[0];
        if (array_key_exists('code', $opt)) {
          $this->maxLength = (int)$opt['code'];
        }
        if (array_key_exists('tabWidth', $opt)) {
          $this->tabWidth = (int)$opt['tabWidth'];
        }
      }
    }
  }

  public function filters() {
    return [
      'Program',
    ];
  }

  private static function computeLineLength(string $line, int $tabWidth) {
    $extraCharacterCount = 0;

    if (preg_match_all('/\t/', $line, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches as $m) {
        $totalOffset = $m[0][1] + $extraCharacterCount;
        $previousTabStopOffset = $tabWidth ? $totalOffset % $tabWidth : 0;
        $spaceCount = $tabWidth - $previousTabStopOffset;
        // -1 for the replaced tab
        $extraCharacterCount += $spaceCount - 1;
      }
    }

    return (strlen($line) + mb_strlen($line, 'utf8')) / 2 + $extraCharacterCount;
  }

  public function Program(&$node) {
    $start = $this->context->sourceCode->getLocation($node->getFullStart());
    $end = $this->context->sourceCode->getLocation($node->getEndPosition());
    $offsets = null;
    for ($i = max($start['line'], $this->lastLine); $i <= $end['line']; $i++) {
      if (is_null($offsets)) {
        $offsets = $this->context->sourceCode->getLineOffsets();
      }
      $lineLength = $offsets[$i] - $offsets[$i - 1];
      if ($lineLength > 1) {
        // need to remove the ending LF character
        $src = $this->context->sourceCode->getSource($offsets[$i - 1], $lineLength - 1);
        if (substr($src, -1) == "\r") {
          $src = substr($src, 0, -1);
        }
        $length = self::computeLineLength($src, $this->tabWidth);
        if ($length > $this->maxLength) {
          $this->report($node, $offsets[$i - 1], "Line {$i} exceeds the maximum line length of {$this->maxLength}.");
        }
      }
    }
    $this->lastLine = max($this->lastLine, $end['line'] + 1);
  }

}

return 'MaxLenRule';

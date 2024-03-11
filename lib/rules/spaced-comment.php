<?php

class SpacedCommentRule extends Rule {

  private $spaced = false;
  private $exceptions = [];

  protected function makeOptions() {
    $this->spaced = empty($this->options)
                  ? false
                  : $this->options[0] === 'always';
    if (count($this->options) >= 2) {
      if (array_key_exists('exceptions', $this->options[1])) {
        $exp = $this->options[1]['exceptions'];
        if (is_string($exp)) {
          $this->exceptions = str_split($exp);
        } elseif (is_array($exp)) {
          $this->exceptions = $exp;
        }
      }
    }
  }

  public function filters() {
    return [
      'Program',
    ];
  }

  private function checkComment(&$token, $offset, string $commentBody, string $text, int $start, int $end = -1) {
    if (strlen($commentBody) > 0) {
      // check begin
      $displayValue = substr($text, $start, 2);
      if ($displayValue[0] !== '/') {
        // # ...
        $displayValue = '#';
      }
      $chr = $commentBody[0];
      $hasSpace = in_array($chr, [' ', "\t", "\r", "\n"]);
      if ($hasSpace && !$this->spaced) {
        if (!in_array($chr, $this->exceptions)) {
          $this->report($token, $offset + $start, "Unexpected space after '$displayValue' in comment.");
        }
      } elseif (!$hasSpace && $this->spaced) {
        if (!in_array($chr, $this->exceptions)) {
          $this->report($token, $offset + $start, "Expected space after '$displayValue' in comment.");
        }
      }
    }
    // check special comment pre colon
    if (preg_match('/^ (TEMP|TODO|FIXME|WORKAROUND|NOTICE|REVIEW)(.?)/', $commentBody, $matches)) {
      $specialCommentPre = $matches[1];
      if ($matches[2] !== ':') {
        $this->report($token, $offset + $start, "Expected a half-width colon after '$specialCommentPre' in comment.");
      }
    }
    if ($end !== -1 && strlen($commentBody) > 1) {
      // check end
      $displayValue = substr($text, $end - 2, 2);
      $chr = substr($commentBody, -1, 1);
      $hasSpace = in_array($chr, [' ', "\t", "\r", "\n"]);
      if ($hasSpace && !$this->spaced) {
        if (!in_array($chr, $this->exceptions)) {
          $this->report($token, $offset + $end - 2, "Unexpected space before '$displayValue' in comment.");
        }
      } elseif (!$hasSpace && $this->spaced) {
        if (!in_array($chr, $this->exceptions)) {
          $this->report($token, $offset + $end - 2, "Expected space before '$displayValue' in comment.");
        }
      }
    }
  }

  private function checkLineComment(&$token, $offset, $text) {
    $start = 0;
    $end = 0;
    while (true) {
      preg_match('/(\/\/|#)(.*)$/mU', $text, $matches, PREG_OFFSET_CAPTURE, $end);
      if (empty($matches)) {
        break;
      }
      $start = $matches[0][1];
      $end = $start + strlen($matches[0][0]);
      $this->checkComment($token, $offset, $matches[2][0], $text, $start);
    }
  }

  public function Program(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);

      $offset = 0;
      $lastOffset = 0;
      while (true) {
        preg_match('/\/\*(.+)\*\//sU', $text, $matches, PREG_OFFSET_CAPTURE, $lastOffset);
        if (!empty($matches)) {
          $offset = $matches[0][1];
          $str = substr($text, $lastOffset, $offset - $lastOffset);
          $this->checkLineComment($token, $token->fullStart + $lastOffset, $str);
          $lastOffset = $offset + strlen($matches[0][0]);
        } else {
          break;
        }
        $this->checkComment($token, $token->fullStart, $matches[1][0], $text, $offset, $lastOffset);
      }
      // last part
      $str = substr($text, $lastOffset);
      $this->checkLineComment($token, $token->fullStart + $lastOffset, $str);
    }
  }
}

Rule::register(__FILE__, 'SpacedCommentRule');

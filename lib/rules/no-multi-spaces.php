<?php

use Microsoft\PhpParser\TokenKind;

class NoMultiSpacesRule extends Rule {

  public function filters() {
    return [
      'Program',
    ];
  }

  public function Program(&$node) {
    $idx = 0;
    foreach ($node->getChildTokens() as $token) {
      $idx++;
      $text = $this->context->sourceCode->getLeadingText($token);
      $modifierOffset = 0;
      if ($idx === 1) {
        // the first one
        $prevToken = $this->getPreviousToken($node, $token);
        // check whether after start tag or not
        if ($prevToken && $prevToken->kind === TokenKind::ScriptSectionStartTag) {
          // combine whitespaces
          $prevText = $this->getTokenText($prevToken);
          if (preg_match('/\s*$/D', $prevText, $matches) && $matches[0]) {
            $text = $matches[0] . $text;
            $modifierOffset += strlen($matches[0]);
          }
        }
      }
      if (strpos($text, "\n") !== false) {
        continue;
      }

      // $text = preg_replace('/\/\/.*$/mU', '', $text);
      $offset = 0;
      $lastOffset = 0;
      while (true) {
        preg_match('/\/\*(.+)\*\//sU', $text, $matches, PREG_OFFSET_CAPTURE, $lastOffset);
        if (empty($matches)) {
          break;
        }
        $offset = $matches[0][1];
        $str = substr($text, $lastOffset, $offset - $lastOffset);
        $displayValue = $matches[0][0];
        if (strlen($str) > 1) {
          $this->report($token, $token->fullStart + $lastOffset - $modifierOffset,
            "Multiple spaces found before '$displayValue'.");
        }
        $lastOffset = $offset + strlen($matches[0][0]);
      }

      // last part
      $str = substr($text, $lastOffset);
      if (strlen($str) > 1) {
        $displayValue = $this->getTokenText($token);
        $this->report($token, $token->fullStart + $lastOffset - $modifierOffset,
          "Multiple spaces found before '$displayValue'.");
      }
    }
  }

}

Rule::register(__FILE__, 'NoMultiSpacesRule');

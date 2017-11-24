<?php

class NoMultiSpacesRule extends Rule {

  public function filters() {
    return [
      'Program',
    ];
  }

  public function Program(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
      if (strpos($text, "\n") !== false) {
        continue;
      }

      // $text = preg_replace('/\/\/.*$/mU', '', $text);
      $offset = 0;
      $lastOffset = 0;
      while (true) {
        preg_match('/\/\*(.+)\*\//sU', $text, $matches, PREG_OFFSET_CAPTURE, $lastOffset);
        if (!empty($matches)) {
          $offset = $matches[0][1];
          $str = substr($text, $lastOffset, $offset - $lastOffset);
          $displayValue = $matches[0][0];
          $lastOffset = $offset + strlen($matches[0][0]);
        } else {
          break;
        }
        if (strlen($str) > 1) {
          $this->report($token, $token->fullStart, "Multiple spaces found before '$displayValue'.");
        }
      }

      // last part
      $str = substr($text, $lastOffset);
      if (strlen($str) > 1) {
        $displayValue = $this->getTokenText($token);
        $this->report($token, $token->fullStart, "Multiple spaces found before '$displayValue'.");
      }
    }
  }

}

return 'NoMultiSpacesRule';

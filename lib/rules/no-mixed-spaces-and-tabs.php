<?php

class NoMixedSpacesAndTabsRule extends Rule {

  public function filters() {
    return [
      'ProgramOnExit',
    ];
  }

  public function ProgramOnExit(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
      if ($text) {
        preg_match('/(\t+ | +\t)/m', $text, $matches, PREG_OFFSET_CAPTURE);
        if (!empty($matches)) {
          $this->report($token, $token->fullStart + $matches[0][1],
            'Mixed spaces and tabs.');
        }
      }
    }
  }

}

Rule::register(__FILE__, 'NoMixedSpacesAndTabsRule');

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
      if ($text && ($a = strpos($text, "\t")) !== false
        && ($b = strpos($text, " ")) !== false) {
        $this->report($token, $token->fullStart + min([$a, $b]),
          'Mixed spaces and tabs.');
      }
    }
  }

}

return 'NoMixedSpacesAndTabsRule';

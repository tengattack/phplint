<?php

use Microsoft\PhpParser\{Node, Token, TokenKind};

class KeywordSpacingRule extends Rule {

  private $spacedBefore = false;
  private $spacedAfter = true;

  static $IGNORE_KEYWORDS = [
    TokenKind::ArrayKeyword,
    // TokenKind::BreakKeyword,
    // TokenKind::ContinueKeyword,
    // TokenKind::DefaultKeyword,
    TokenKind::EmptyKeyword,
    // TokenKind::EndDeclareKeyword,
    // TokenKind::EndForKeyword,
    // TokenKind::EndForEachKeyword,
    // TokenKind::EndIfKeyword,
    // TokenKind::EndSwitchKeyword,
    // TokenKind::EndWhileKeyword,
    // TokenKind::ExitKeyword,
    TokenKind::IsSetKeyword,
    TokenKind::ListKeyword,
    // TokenKind::ReturnKeyword,
    TokenKind::UnsetKeyword,
  ];

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (array_key_exists('before', $opt)) {
        $this->spacedBefore = !!$opt['before'];
      }
      if (array_key_exists('after', $opt)) {
        $this->spacedAfter = !!$opt['after'];
      }
    }
  }

  public function filters() {
    return [
      'Keyword',
    ];
  }

  private function reportNoBeginningSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "There should be no space after '$tokenValue'.");
  }

  private function reportNoEndingSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "There should be no space before '$tokenValue'.");
  }

  private function reportRequiredBeginningSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "A space is required after '$tokenValue'.");
  }

  private function reportRequiredEndingSpace(&$token) {
    $tokenValue = $this->getTokenText($token);
    $this->report($token, "A space is required before '$tokenValue'.");
  }

  public function Keyword(&$token) {
    if (in_array($token->kind, KeywordSpacingRule::$IGNORE_KEYWORDS)) {
      // ignored
      return;
    }
    // TODO: find next walking in parent
    $node = $this->context->current();
    $position = $token->getEndPosition();

    $nextChild = null;
    $prevToken = null;
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child->getFullStart() >= $position) {
        $nextChild = $child;
        break;
      } else {
        if ($child instanceof Token) {
          $prevToken = $child;
        } else {
          $prevToken = null;
        }
      }
    }

    // ignore keyword before spacing checking after (
    if (!$prevToken || $prevToken->kind === TokenKind::OpenParenToken) {
      $hasSpace = $this->isSpaceBeforeToken($token, $this->spacedBefore);
      if ($hasSpace && !$this->spacedBefore) {
        $this->reportNoEndingSpace($token);
      } elseif (!$hasSpace && $this->spacedBefore) {
        $this->reportRequiredEndingSpace($token);
      }
    }

    if ($nextChild instanceof Node) {
      $hasSpace = $this->isSpaceBeforeNode($nextChild, $this->spacedAfter);
    } else if ($nextChild instanceof Token) {
      if ($nextChild->kind === TokenKind::SemicolonToken
         || $nextChild->kind === TokenKind::ColonToken) {
        // ignore keyword after spacing checking before ; or :
        return;
      }
      $hasSpace = $this->isSpaceBeforeToken($nextChild, $this->spacedAfter);
    } else {
      return;
    }

    // found next child
    if ($hasSpace && !$this->spacedAfter) {
      $this->reportNoBeginningSpace($token);
    } elseif (!$hasSpace && $this->spacedAfter) {
      $this->reportRequiredBeginningSpace($token);
    }
  }

}

return 'KeywordSpacingRule';

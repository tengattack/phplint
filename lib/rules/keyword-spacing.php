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
    TokenKind::IncludeKeyword,
    TokenKind::IncludeOnceKeyword,
    TokenKind::IsSetKeyword,
    TokenKind::ListKeyword,
    TokenKind::RequireKeyword,
    TokenKind::RequireOnceKeyword,
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
      'Tag',
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
    $prevToken = $this->getPreviousToken($node, $token);
    foreach ($node->getChildNodesAndTokens() as $child) {
      if ($child->getFullStart() >= $position) {
        $nextChild = $child;
        break;
      }
    }

    // ignore keyword before spacing checking after (, [ or script start
    if (!$prevToken || (
        $prevToken->kind !== TokenKind::OpenParenToken
        && $prevToken->kind !== TokenKind::OpenBracketToken
        && $prevToken->kind !== TokenKind::ScriptSectionStartTag
      )) {
      $hasSpace = $this->isSpaceBeforeToken($token, $this->spacedBefore);
      if ($hasSpace && !$this->spacedBefore) {
        $this->reportNoEndingSpace($token);
      } elseif (!$hasSpace && $this->spacedBefore) {
        $this->reportRequiredEndingSpace($token);
      }
    }

    if ($token->kind === TokenKind::DeclareKeyword) {
      // http://php.net/manual/zh/control-structures.declare.php
      // DeclareStatement has special structure, ignore check after spaces
      // if it is followed by SemicolonToken
      $lastChild = null;
      foreach ($node->getChildNodesAndTokens() as $child) {
        if ($child->getFullStart() >= $position) {
          $lastChild = $child;
        }
      }
      if ($lastChild && $lastChild instanceof Token
          && $lastChild->kind === TokenKind::SemicolonToken) {
        return;
      }
    }
    if ($nextChild instanceof Node) {
      $hasSpace = $this->isSpaceBeforeNode($nextChild, $this->spacedAfter);
    } elseif ($nextChild instanceof Token) {
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

  public function Tag(&$token) {
    switch ($token->kind) {
    case TokenKind::ScriptSectionStartTag:
      $tag = $this->getTokenText($token);
      if ($tag === '<?=') {
        // ensure `<?= $foo` has spaces inside
        $nextToken = $this->getNextToken($this->context->current(), $token);
        if ($nextToken) {
          $hasSpace = $this->isSpaceBeforeToken($nextToken, $this->spacedAfter);
          if ($hasSpace && !$this->spacedAfter) {
            $this->reportNoBeginningSpace($token);
          } elseif (!$hasSpace && $this->spacedAfter) {
            $this->reportRequiredBeginningSpace($token);
          }
        }
      }
      break;
    case TokenKind::ScriptSectionEndTag:
      $hasSpace = $this->isSpaceBeforeToken($token, $this->spacedBefore);
      if ($hasSpace && !$this->spacedBefore) {
        $this->reportNoEndingSpace($token);
      } elseif (!$hasSpace && $this->spacedBefore) {
        $this->reportRequiredEndingSpace($token);
      }
      break;
    }
  }

}

return 'KeywordSpacingRule';

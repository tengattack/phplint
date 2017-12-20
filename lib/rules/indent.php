<?php

use Microsoft\PhpParser\TokenKind;

class TokenInfo {

  public $context;

  function __construct($context) {
    $this->context = $context;
  }

  public function isFirstTokenOfLine(&$token) {
    return false;
  }

  public function getLeadingCommentAndWhitespaceText(&$token) {
    return $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
  }

}

class Offset {
  public $start;
  public $end;
  public $token;
  public $indent;

  function __construct(int $start, int $end, $token, int $indent) {
    $this->start = $start;
    $this->end = $end;
    $this->token = $token;
    $this->indent = $indent;
  }
}

class OffsetStorage {

  public $tokenInfo;
  public $indent;
  public $offsetMap;

  function __construct($tokenInfo, int $indent = 0) {
    $this->tokenInfo = $tokenInfo;
    $this->indent = $indent;
    $rootOffset = new Offset(0, strlen($tokenInfo->context->astNode->fileContents), null, $indent);
    $this->offsetMap = [ $rootOffset ];
  }

  public function setDesiredOffsets($range, &$token, int $offsetValue = 1) {
    if ($range[1] <= $range[0]) {
      // ignore empty range
      return;
    }
    $newOffset = new Offset($range[0], $range[1], $token, $this->indent + $offsetValue);
    foreach ($this->offsetMap as $i => &$offset) {
      if ($offset->start === $range[0] && $offset->end === $range[1]) {
        // replace
        $offset->start = $range[0];
        $offset->end = $range[1];
        $offset->token = $token;
        return;
      } elseif ($offset->start === $range[0] && $offset->end > $range[1]) {
        // insert to left
        $offset->end = $range[1];
        $newOffset->indent = $offset->indent + $offset;
        array_splice($this->offsetMap, $i, 0, [ $newOffset ]);
        return;
      } elseif ($offset->start < $range[0] && $offset->end === $range[1]) {
        // insert to right
        $offset->start = $range[0];
        $newOffset->indent = $offset->indent + $offsetValue;
        array_splice($this->offsetMap, $i - 1, 0, [ $newOffset ]);
        return;
      } elseif ($offset->start < $range[0] && $offset->end > $range[1]) {
        // insert to center
        $left = new Offset($offset->start, $range[0], $offset->token, $offset->indent);
        $right = new Offset($range[1], $offset->end, $offset->token, $offset->indent);
        $newOffset->indent = $offset->indent + $offsetValue;
        array_splice($this->offsetMap, $i, 1, [ $left, $newOffset, $right ]);
        return;
      }
    }
    if (count($this->offsetMap) > 0 && $this->offsetMap[0]->start > $range[0]) {
      // insert to head
      $offset0 = &$this->offsetMap[0];
      if ($offset0->start < $range[1]) {
        $offset0->start = $range[1];
      }
      array_unshift($this->offsetMap, $newOffset);
    } else {
      $this->offsetMap []= $newOffset;
    }
  }

  public function getDesiredIndentByPosition(int $position) {
    foreach ($this->offsetMap as $offset) {
      if ($offset->start <= $position && $offset->end > $position) {
        return $offset->indent;
      }
    }
    return 0;
  }

  public function getDesiredIndent(&$token) {
    return $this->getDesiredIndentByPosition($token->start);
  }

}

class IndentRule extends Rule {

  private $indentType = 'space';
  private $indentSize = 4;
  private $offsets;
  private $tokenInfo;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (is_string($opt) && $opt === 'tab') {
        $this->indentType = 'tab';
        $this->indentSize = 1;
      } elseif (is_numeric($opt)) {
        $this->indentSize = (int)$opt;
      }
    }
    $this->tokenInfo = new TokenInfo($this->context);
    $this->offsets = new OffsetStorage($this->tokenInfo);
  }

  public function filters() {
    return [
      'Program',
      'ProgramOnExit',
    ];
  }

  protected function pluralize(string $word, int $count): string {
    return ($count === 1 ? $word : "{$word}s");
  }

  protected function createErrorMessage($expectedAmount, $actualSpaces, $actualTabs) {
    $expectedStatement = "$expectedAmount " . $this->pluralize($this->indentType, $expectedAmount); // e.g. "2 tabs"
    $foundSpacesWord = $this->pluralize('space', $actualSpaces); // e.g. "space"
    $foundTabsWord = $this->pluralize('tab', $actualTabs); // e.g. "tabs"
    $foundStatement = '';

    if ($actualSpaces > 0) {

      /*
       * Abbreviate the message if the expected indentation is also spaces.
       * e.g. 'Expected 4 spaces but found 2' rather than 'Expected 4 spaces but found 2 spaces'
       */
      $foundStatement = $this->indentType === 'space' ? $actualSpaces : "${actualSpaces} ${foundSpacesWord}";
    } elseif ($actualTabs > 0) {
      $foundStatement = $this->indentType === 'tab' ? $actualTabs : "${actualTabs} ${foundTabsWord}";
    } else {
      $foundStatement = "0";
    }

    return "Expected indentation of $expectedStatement but found $foundStatement.";
}

  protected function reportIndentError($token, $start, $expectedAmount, $indentText) {
    if (strpos($indentText, ' ') !== false && strpos($indentText, "\t") !== false) {
      // To avoid conflicts with no-mixed-spaces-and-tabs, don't report mixed spaces and tabs.
    } else {
      $actualSpaces = 0;
      $actualTabs = 0;
      $len = strlen($indentText);
      if ($len > 0) {
        if ($indentText[0] === ' ') {
          $actualSpaces = $len;
        } else {
          $actualTabs = $len;
        }
      }
      $this->report($token, $start,
        $this->createErrorMessage($expectedAmount, $actualSpaces, $actualTabs));
    }
  }

  public function Program(&$node) {
    $kindName = $node->getNodeKindName();
    switch ($kindName) {
    case 'BinaryExpression':
      $token = $node->getChildTokens()->current();
      if ($token) {
        // binary expression
        $this->offsets->setDesiredOffsets([$token->start, $node->getEndPosition()], $token);
      }
      return;
    case 'StringLiteral':
      $token = $node->getChildTokens()->current();
      if ($token) {
        $this->offsets->setDesiredOffsets([$token->start + 1, $node->getEndPosition() - 1], $token);
      }
      return;
    }
    // common
    $openBrace = null;
    $closeBrace = null;
    $openParen = null;
    $closeParen = null;
    $openBracket = null;
    $closeBracket = null;
    foreach ($node->getChildTokens() as $token) {
      switch ($token->kind) {
      case TokenKind::OpenBraceToken:
        $openBrace = $token;
        break;
      case TokenKind::OpenParenToken:
        $openParen = $token;
        break;
      case TokenKind::OpenBracketToken:
        $openBracket = $token;
        break;
      case TokenKind::CloseBraceToken:
        $closeBrace = $token;
        break;
      case TokenKind::CloseParenToken:
        $closeParen = $token;
        break;
      case TokenKind::CloseBracketToken:
        $closeBracket = $token;
        break;
      case TokenKind::ArrowToken:
      case TokenKind::DoubleArrowToken:
      case TokenKind::ColonToken:
      case TokenKind::ColonColonToken:
        if ($this->isNewLineBeforeToken($token) || $this->isNewLineAfterToken($token, $node)) {
          $this->offsets->setDesiredOffsets([$token->start, $node->getEndPosition()], $token);
        }
        break;
      }
    }
    if ($openBrace && $closeBrace) {
      if ($this->isNewLineAfterToken($openBrace, $node)) {
        $this->offsets->setDesiredOffsets([$openBrace->start + 1, $closeBrace->start], $openBrace);
      }
    }
    if ($openParen && $closeParen) {
      if ($this->isNewLineAfterToken($openParen, $node)) {
        $this->offsets->setDesiredOffsets([$openParen->start + 1, $closeParen->start], $openParen);
      }
    }
    if ($openBracket && $closeBracket) {
      if ($this->isNewLineAfterToken($openBracket, $node)) {
        $this->offsets->setDesiredOffsets([$openBracket->start + 1, $closeBracket->start], $openBracket);
      }
    }
  }

  public function ProgramOnExit(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $desiredIndent = $this->offsets->getDesiredIndent($token);
      $text = $this->tokenInfo->getLeadingCommentAndWhitespaceText($token);
      $lines = explode("\n", $text);
      $offset = 0;
      if (count($lines) > 1) {
        $inDocComment = false;
        foreach ($lines as $i => $line) {
          if (preg_match('/^([ \t]*)(\S*)/', $line, $matches)) {
            if ($i < count($lines) - 1 && !$matches[2]) {
              // not last line
              $offset += strlen($line) + 1;
              continue;
            }
            $indentText = $matches[1];
            $expectedAmount = $desiredIndent * $this->indentSize + ($inDocComment ? 1 : 0);
            if (strlen($indentText) !== $expectedAmount) {
              $this->reportIndentError($token, $token->fullStart + $offset, $expectedAmount, $indentText);
            }
            if (substr($matches[2], 0, 3) === '/**') {
              $inDocComment = true;
            } elseif (strpos($matches[2], '*/') !== false) {
              $inDocComment = false;
            }
          }
          $offset += strlen($line) + 1;
        }
      }
      if ($token->kind === TokenKind::StringLiteralToken) {
        $text = $this->getTokenText($token);
        $lines = explode("\n", $text);
        $offset = 0;
        if (count($lines) <= 1) {
          return;
        }
        for ($i = 0; $i < count($lines); $i++) {
          $line = $lines[$i];
          if ($i <= 0) {
            $offset += strlen($line) + 1;
            continue;
          }
          if (preg_match('/^([ \t]*)(\S*)/', $line, $matches)) {
            if ($i < count($lines) - 1 && !$matches[2]) {
              // not last line
              $offset += strlen($line) + 1;
              continue;
            }
          }
          $indentText = $matches[1];
          $indentLength = strlen($indentText);
          $desiredIndent = $this->offsets->getDesiredIndentByPosition($token->start + $offset + $indentLength);
          $expectedAmount = $desiredIndent * $this->indentSize;
          if ($indentLength !== $expectedAmount) {
            $this->reportIndentError($token, $token->start + $offset, $expectedAmount, $indentText);
          }
          $offset += strlen($line) + 1;
        }
      }
    }
  }

}

return 'IndentRule';

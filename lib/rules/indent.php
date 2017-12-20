<?php

use Microsoft\PhpParser\TokenKind;

class TokenInfo {

  public $sourceCode;

  function __construct(&$sourceCode) {
    $this->sourceCode = $sourceCode;
  }

  public function getLeadingText(&$token) {
    return $this->sourceCode->getLeadingText($token);
  }

  public function getLine(int $pos) {
    return $this->sourceCode->getLocation($pos)['line'];
  }

}

class OffsetLine {
  public $start;
  public $end;
  public $token;
  public $indent;
  public $offset;

  function __construct(int $start, int $end, $token, int $indent, int $offset) {
    $this->start = $start;
    $this->end = $end;
    $this->token = $token;
    $this->indent = $indent;
    $this->offset = $offset;
  }

  function getIndent() {
    return $this->indent + $this->offset;
  }
}

class OffsetStorage {

  public $tokenInfo;
  public $indent;
  public $offsetMap;

  function __construct($tokenInfo, int $indent = 0) {
    $this->tokenInfo = $tokenInfo;
    $this->indent = $indent;
    $endLine = $this->tokenInfo->getLine(strlen($tokenInfo->sourceCode->astNode->fileContents));
    $rootOffset = new OffsetLine(0, $endLine, null, $indent, 0);
    $this->offsetMap = [ $rootOffset ];
  }

  public function setDesiredOffsets($range, $token, $endToken = null, int $offsetValue = 1) {
    if ($range[1] <= $range[0]) {
      // ignore empty range
      return;
    }

    // next line of start position
    $range[0] = $this->tokenInfo->getLine($range[0]) + 1;
    $range[1] = $this->tokenInfo->getLine($range[1]);
    if ($range[1] < $range[0]) {
      return;
    }

    $newOffset = new OffsetLine($range[0], $range[1], $token, $this->indent, $offsetValue);
    foreach ($this->offsetMap as $i => &$offset) {
      if ($offset->start === $range[0] && $offset->end === $range[1]) {
        // replace
        // $this->offsetMap[$i] = $newOffset;
        return;
      } elseif ($offset->start === $range[0] && $offset->end > $range[1]) {
        // insert to left
        $offset->start = $range[1] + 1;
        $newOffset->indent = $offset->indent;
        $endLine = $endToken ? $this->tokenInfo->getLine($endToken->start) : 0;
        if ($endLine > $range[1]) {
          $offset->offset = 0;
        }
        array_splice($this->offsetMap, $i, 0, [ $newOffset ]);
        return;
      } elseif ($offset->start < $range[0] && $offset->end === $range[1]) {
        // insert to right
        $offset->end = $range[0] - 1;
        $newOffset->indent = $offset->indent + $offset->offset;
        array_splice($this->offsetMap, $i + 1, 0, [ $newOffset ]);
        return;
      } elseif ($offset->start < $range[0] && $offset->end > $range[1]) {
        // insert to center
        $left = new OffsetLine($offset->start, $range[0] - 1, $offset->token, $offset->indent, $offset->offset);
        $right = new OffsetLine($range[1] + 1, $offset->end, $offset->token, $offset->indent, $offset->offset);
        $newOffset->indent = $offset->indent + $offset->offset;
        array_splice($this->offsetMap, $i, 1, [ $left, $newOffset, $right ]);
        return;
      }
    }
    if (count($this->offsetMap) > 0 && $this->offsetMap[0]->start > $range[0]) {
      // insert to head
      $offset0 = &$this->offsetMap[0];
      if ($offset0->start < $range[1]) {
        $offset0->start = $range[1] + 1;
      }
      array_unshift($this->offsetMap, $newOffset);
    } else {
      $this->offsetMap []= $newOffset;
    }
  }

  public function getDesiredIndentByPosition(int $position) {
    $line = $this->tokenInfo->getLine($position);
    foreach ($this->offsetMap as $offset) {
      if ($offset->start <= $line && $offset->end >= $line) {
        return $offset->getIndent();
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
  private $indentOpts = [
    'SwitchCase' => 0,
  ];
  private $offsets;
  private $tokenInfo;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (is_string($opt) && $opt === 'tab') {
        $this->indentType = 'tab';
        $this->indentSize = 1;
        $opt = $this->options[1] ?? null;
      } elseif (is_numeric($opt)) {
        $this->indentSize = (int)$opt;
        $opt = $this->options[1] ?? null;
      }
      if (is_array($opt)) {
        foreach ($this->indentOpts as $key => $_) {
          if (array_key_exists($key, $opt)) {
            $this->indentOpts[$key] = (int)$opt[$key];
          }
        }
      }
    }
    $this->tokenInfo = new TokenInfo($this->context->sourceCode);
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
      $parent = $node;
      do {
        $parent = $parent->parent;
        $parentKindName = $parent ? $parent->getNodeKindName() : '';
      } while ($parentKindName === $kindName);
      if ($parentKindName === 'ExpressionStatement') {
        // binary expression under expression statement
        $token = $node->getChildTokens()->current();
        if ($token) {
          $this->offsets->setDesiredOffsets([$token->fullStart, $node->getEndPosition()], $token);
        }
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
      case TokenKind::ColonColonToken:
      case TokenKind::EqualsToken:
        $this->offsets->setDesiredOffsets([$token->fullStart, $node->getEndPosition()], $token);
        break;
      case TokenKind::ColonToken:
        $offset = 1;
        if ($kindName === 'SwitchStatementNode') {
          $offset = $this->indentOpts['SwitchCase'];
        }
        $this->offsets->setDesiredOffsets([$token->fullStart, $node->getEndPosition()], $token, null, $offset);
        break;
      }
    }
    if ($openBrace && $closeBrace) {
      $offset = 1;
      if ($kindName === 'SwitchStatementNode') {
        $offset = $this->indentOpts['SwitchCase'];
      }
      $this->offsets->setDesiredOffsets([$openBrace->start + 1, $closeBrace->fullStart], $openBrace, $closeBrace, $offset);
    }
    if ($openParen && $closeParen) {
      $this->offsets->setDesiredOffsets([$openParen->start + 1, $closeParen->fullStart], $openParen, $closeParen);
    }
    if ($openBracket && $closeBracket) {

      $this->offsets->setDesiredOffsets([$openBracket->start + 1, $closeBracket->fullStart], $openBracket, $closeBracket);
    }
  }

  public function ProgramOnExit(&$node) {
    foreach ($node->getChildTokens() as $token) {
      $desiredIndent = $this->offsets->getDesiredIndent($token);
      $text = $this->tokenInfo->getLeadingText($token);
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
      if (defined('VERBOSE') && VERBOSE > 1) {
        if ($token->kind === TokenKind::EndOfFileToken) {
          print_r($this->offsets->offsetMap);
        }
      }
    }
  }

}

return 'IndentRule';

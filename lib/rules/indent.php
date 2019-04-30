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
    $range[1] = $this->tokenInfo->getLine($range[1]) - (count($range) > 2 ? $range[2] : 0);
    if ($range[1] < $range[0]) {
      return;
    }

    $newOffset = new OffsetLine($range[0], $range[1], $token, 0, $offsetValue);
    foreach ($this->offsetMap as $i => &$offset) {
      if ($offset->start === $range[0] && $offset->end === $range[1]) {
        // replace
        $newOffset->indent = $offset->indent;
        $this->offsetMap[$i] = $newOffset;
        return;
      } elseif ($offset->start === $range[0] && $offset->end > $range[1]) {
        // insert to left
        $offset->start = $range[1] + 1;
        $endLine = $endToken ? $this->tokenInfo->getLine($endToken->start) : 0;
        if ($offset->end === $endLine) {
          $newOffset->indent = $offset->indent;
          $offset->offset = 0;
        } else {
          $newOffset->indent = $offset->indent + $offset->offset;
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
        return $this->indent + $offset->getIndent();
      }
    }
    return $this->indent;
  }

  public function getDesiredIndent(&$token) {
    return $this->getDesiredIndentByPosition($token->start);
  }

}

class IndentRule extends Rule {

  private $indentType = 'space';
  private $indentSize = 4;
  private $indentOpts = [
    'SwitchCase' => 1,
    'FunctionDeclaration' => [
      'parameters' => 2,
    ],
    'Condition' => 2,
  ];
  private $offsets;
  private $tokenInfo;

  static $EndSectionKeywords = [
    TokenKind::EndDeclareKeyword,
    TokenKind::EndForKeyword,
    TokenKind::EndForEachKeyword,
    TokenKind::EndIfKeyword,
    TokenKind::EndSwitchKeyword,
    TokenKind::EndWhileKeyword
  ];

  static $OneLineStatements = [
    'ForStatement',
    'ForeachStatement',
    'IfStatementNode',
    'WhileStatement',
    'DoStatement',
  ];

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
            if (is_array($this->indentOpts[$key])) {
              foreach ($this->indentOpts[$key] as $subkey => $_) {
                if (array_key_exists($subkey, $opt[$key]))
                $this->indentOpts[$key][$subkey] = (int)$opt[$key][$subkey];
              }
            } else {
              $this->indentOpts[$key] = (int)$opt[$key];
            }
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
    case 'InlineHtml':
      return;
    case 'BinaryExpression':
    case 'TernaryExpression':
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
    case 'ReturnStatement':
    case 'CaseStatementNode':
      $token = $node->getChildTokens()->current();
      if ($token) {
        $this->offsets->setDesiredOffsets([$token->start, $node->getEndPosition()], $token);
      }
      return;
    case 'ExpressionStatement':
      $parentKindName = $node->parent ? $node->parent->getNodeKindName() : '';
      if (in_array($parentKindName, self::$OneLineStatements)) {
        $token = $node->getDescendantTokens()->current();
        if ($token) {
          $this->offsets->setDesiredOffsets([$token->start, $node->getEndPosition()], $token);
        }
      }
      return;
    case 'MemberAccessExpression':
      $parent = $node;
      do {
        $parent = $parent->parent;
        $parentKindName = $parent ? $parent->getNodeKindName() : '';
      } while (in_array($parentKindName, ['MemberAccessExpression', 'CallExpression']));
      if ($parentKindName === 'ExpressionStatement') {
        // member access expression under expression statement
        $token = $node->getChildTokens()->current();
        if ($token) {
          $this->offsets->setDesiredOffsets([$token->fullStart, $node->getEndPosition()], $token);
        }
      }
      return;
    case 'ParameterDeclarationList':
      $token = $node->getDescendantTokens()->current();
      if ($token) {
        $offset = $this->indentOpts['FunctionDeclaration']['parameters'];
        $this->offsets->setDesiredOffsets([$node->getFullStart(), $node->getEndPosition()], $token, null, $offset);
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
      case TokenKind::UseKeyword:
      case TokenKind::EqualsToken:
        $this->offsets->setDesiredOffsets([$token->fullStart, $node->getEndPosition()], $token);
        break;
      case TokenKind::ColonToken:
        $offset = 1;
        if (in_array($kindName, ['FunctionDeclaration', 'MethodDeclaration'])) {
          // defined function return type
          // do need to handle
          break;
        } elseif ($kindName === 'SwitchStatementNode') {
          $offset = $this->indentOpts['SwitchCase'];
        }
        $endPosition = $node->getEndPosition();
        $endToken = null;
        foreach ($node->getChildTokens() as $subToken) {
          // until end keywords
          if (in_array($subToken->kind, self::$EndSectionKeywords)) {
            $endPosition = $subToken->start - 1;
            $endToken = $subToken;
            break;
          }
        }
        $this->offsets->setDesiredOffsets([$token->fullStart, $endPosition], $token, $endToken, $offset);
        break;
      }
    }
    if ($openBrace && $closeBrace) {
      $offset = 1;
      if ($kindName === 'SwitchStatementNode') {
        $offset = $this->indentOpts['SwitchCase'];
      }
      $parentKindName = $node->parent ? $node->parent->getNodeKindName() : '';
      $mergeBlock = !in_array($parentKindName, self::$OneLineStatements);
      $this->offsets->setDesiredOffsets([$openBrace->start + 1, $closeBrace->start, 1], $openBrace, $mergeBlock ? $closeBrace : null, $offset);
    }
    if ($openParen && $closeParen) {
      $offset = 1;
      if (strpos($kindName, 'Statement') !== false || $kindName === 'ElseIfClauseNode') {
        $offset = $this->indentOpts['Condition'];
      }
      $this->offsets->setDesiredOffsets([$openParen->start + 1, $closeParen->fullStart], $openParen, $closeParen, $offset);
    }
    if ($openBracket && $closeBracket) {
      $this->offsets->setDesiredOffsets([$openBracket->start + 1, $closeBracket->fullStart], $openBracket, $closeBracket);
    }
  }

  public function ProgramOnExit(&$node) {
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::HeredocStart) {
        break;
      }
      if ($token->kind === TokenKind::ScriptSectionStartTag) {
        // realign indent
        $loc = $this->context->sourceCode->getLocation($token->start);
        $this->offsets->indent = (int)($loc['column'] / $this->indentSize);
      }
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
            if ($i > 0) {
              $indentText = $matches[1];
              if ($token->kind === TokenKind::CloseBraceToken) {
                // it may have different indent before close brace token
                $desiredIndent = $this->offsets->getDesiredIndentByPosition($token->fullStart + $offset);
              }
              $expectedAmount = $desiredIndent * $this->indentSize + ($inDocComment ? 1 : 0);
              if (strlen($indentText) !== $expectedAmount) {
                $this->reportIndentError($token, $token->fullStart + $offset, $expectedAmount, $indentText);
              }
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
      if ($token->kind === TokenKind::ScriptSectionEndTag) {
        // reset indent
        $this->offsets->indent = 0;
      } elseif (in_array($token->kind, [TokenKind::StringLiteralToken, TokenKind::EncapsedAndWhitespace])) {
        // TokenKind::EncapsedAndWhitespace also could appears in Node::StringLiteral
        $text = $this->getTokenText($token);
        $lines = explode("\n", $text);
        $offset = 0;
        if (count($lines) <= 1) {
          continue;
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

Rule::register(__FILE__, 'IndentRule');

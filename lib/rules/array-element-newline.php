<?php

use Microsoft\PhpParser\{Token, TokenKind};

class ArrayElementNewlineRule extends Rule {

  private $multiline = false;
  private $minItems = null;

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (is_string($opt)) {
        $this->minItems = $opt === 'always' ? 0 : null;
      } elseif (is_array($opt)) {
        if (array_key_exists('minItems', $opt)) {
          $this->minItems = $opt['minItems'];
        }
        if (array_key_exists('multiline', $opt)) {
          $this->multiline = !!$opt['multiline'];
        }
      }
    }
  }

  public function filters() {
    return [
      'ArrayElementList',
    ];
  }

  public function reportNoLineBreak(&$child) {
    $this->report($child, 'There should be no linebreak here.');
  }

  public function reportRequiredLineBreak(&$child) {
    if ($child instanceof Token) {
      $displayValue = "'" . $this->getTokenText($child) . "'";
    } else {
      $displayValue = 'this element';
    }
    $this->report($child, "There should be a linebreak before $displayValue.");
  }

  public function ArrayElementList(&$node) {
    $newline = 0;
    $sameline = 0;
    $item = 0;
    $beforeNewline = false;
    $firstNewline = false;
    foreach ($node->getChildNodes() as $child) {
      if ($item <= 1) {
        $text = $child->getLeadingCommentAndWhitespaceText();
        if ($item === 0) {
          $beforeNewline = strpos($text, "\n") !== false;
        } else {
          $firstNewline = strpos($text, "\n") !== false;
        }
      }
      $item++;
    }

    $idx = 0;
    foreach ($node->getChildNodes() as $child) {
      if ($idx > 0) {
        $text = $child->getLeadingCommentAndWhitespaceText();
        if (strpos($text, "\n") !== false) {
          if ($this->minItems !== null && $item < $this->minItems) {
            $this->reportNoLineBreak($child);
          } elseif ($this->multiline && !$firstNewline) {
            $this->reportNoLineBreak($child);
          }
          $newline++;
        } else {
          if ($this->minItems !== null && $item >= $this->minItems) {
            $this->reportRequiredLineBreak($child);
          } elseif ($this->multiline && $firstNewline) {
            $this->reportRequiredLineBreak($child);
          }
          $sameline++;
        }
      }
      $idx++;
    }

    if ($this->multiline && $item > 0) {
      $parent = $node->parent;
      if (!$parent || $parent->getNodeKindName() !== 'ArrayCreationExpression') {
        return;
      }
      $position = $node->getEndPosition();
      foreach ($parent->getChildTokens() as $token) {
        if ($token->getEndPosition() <= $position) {
          continue;
        }
        if ($token->kind === TokenKind::CloseParenToken
          || $token->kind === TokenKind::CloseBracketToken) {
          // array end newline
          $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
          $hasNewline = strpos($text, "\n") !== false;
          if ($hasNewline && !$beforeNewline) {
            $this->reportNoLineBreak($token);
          } elseif (!$hasNewline && $beforeNewline) {
            $this->reportRequiredLineBreak($token);
          }
          break;
        }
      }
    }
  }

}

Rule::register(__FILE__, 'ArrayElementNewlineRule');

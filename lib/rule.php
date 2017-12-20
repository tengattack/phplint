<?php

use Microsoft\PhpParser\Token;

class Rule {

  protected $context;
  protected $id;
  protected $severity;
  protected $options = [];

  static $SEVERITY = [
    'off' => 0,
    'warn' => 1,
    'error' => 2,
    'fatal' => 3,
  ];

  static function severityToString(int $severity) {
    foreach (Rule::$SEVERITY as $label => $val) {
      if ($val === $severity) {
        return $label;
      }
    }
    return 'unknown';
  }

  static function removeComments(string $str): string {
    // remove comments
    // type: /* ... */
    $str = preg_replace('/\/\*.+\*\//sU', '', $str);
    // type: // ... or # ...
    $str = preg_replace('/(\/\/|#).*$/mU', '', $str);
    return $str;
  }

  static function isSpace(string $str, bool $withNewLine): bool {
    $str = Rule::removeComments($str);
    if ($withNewLine) {
      preg_match('/^\s+$/', $str, $matches);
    } else {
      preg_match('/^[ \t]+$/', $str, $matches);
    }
    return !empty($matches);
  }

  function __construct(&$context, string $id, $data) {
    $this->context = $context;
    $this->id = $id;
    $this->parseSeverityAndOptions($data);
    $this->makeOptions();
  }

  public function filters() {
    return [];
  }

  public function getTokenText(&$token) {
    return $token->getText($this->context->astNode->fileContents);
  }

  public function getDescendantLastToken(&$node) {
    $lastToken = null;
    foreach ($node->getDescendantNodesAndTokens() as $child) {
      if ($child instanceof Token) {
        $lastToken = $child;
      } else {
        $lastToken = null;
      }
    }
    return $lastToken;
  }

  public function getPreviousToken(&$node, &$token) {
    $parent = $node;
    $current = $token;
    $prevToken = null;

    while ($parent !== null) {
      $isfirst = true;
      foreach ($parent->getChildNodesAndTokens() as $child) {
        if ($child === $current) {
          if (!$isfirst) {
            return $prevToken;
          }
        } else if ($child instanceof Token) {
          $isfirst = false;
          $prevToken = $child;
        } else {
          $isfirst = false;
          $prevToken = $this->getDescendantLastToken($child);
        }
      }
      $current = $parent;
      $parent = $parent->parent;
    }

    return null;
  }

  public function getNextToken(&$node, &$token) {
    $parent = $node;
    $current = $token;
    $checkNext = false;

    while ($parent !== null) {
      $checkNext = false;
      foreach ($parent->getChildNodesAndTokens() as $child) {
        if ($child === $current) {
          $checkNext = true;
        } else if ($child instanceof Token) {
          if ($checkNext) {
            return $child;
          }
        } else {
          if ($checkNext) {
            return $child->getDescendantTokens()->current();
          }
        }
      }
      $current = $parent;
      $parent = $parent->parent;
    }

    return null;
  }

  public function isSpaceBeforeToken(&$token, bool $withNewLine = false): bool {
    $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
    return Rule::isSpace($text, $withNewLine);
  }

  public function isSpaceBeforeNode(&$node, bool $withNewLine = false): bool {
    $text = $node->getLeadingCommentAndWhitespaceText();
    return Rule::isSpace($text, $withNewLine);
  }

  public function isNewLineBeforeNode(&$node): bool {
    $text = $node->getLeadingCommentAndWhitespaceText();
    return Rule::isSpace($text, true) && strpos($text, "\n") !== false;
  }

  public function isNewLineBeforeToken(&$token): bool {
    $text = $token->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
    return strpos($text, "\n") !== false;
  }

  public function isNewLineAfterToken(&$token, &$node): bool {
    $nextToken = $this->getNextToken($node, $token);
    if (!$nextToken) {
      return false;
    }
    $text = $nextToken->getLeadingCommentsAndWhitespaceText($this->context->astNode->fileContents);
    return strpos($text, "\n") !== false;
  }

  public function parseSeverityAndOptions($data) {
    $level = 0;
    if (is_array($data)) {
      $level = $data[0];
      $this->options = array_slice($data, 1);
    } else {
      $level = $data;
    }
    if (is_string($level)) {
      if (array_key_exists($level, Rule::$SEVERITY)) {
        $this->severity = Rule::$SEVERITY[$level];
        return;
      }
    } else if (is_numeric($level)) {
      $level = (int)$level;
      if ($level >= 0 && $level <= 3) {
        $this->severity = $level;
        return;
      }
    }
    throw new \Exception("Unexcepted severity: $level");
  }

  protected function makeOptions() { }

  public function report(&$node, $pos = null, $message = null, $data = null, $fix = null) {
    // $pos starts from 0
    $this->context->report($this->id, $this->severity,
      $node, $pos, $message, $data, $fix);
  }

}

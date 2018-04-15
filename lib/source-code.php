<?php

class SourceCode {

  public $astNode;
  public $filePath;
  private $sourceLineOffsets;

  function __construct($astNode, $filePath) {
    $this->astNode = $astNode;
    $this->filePath = $filePath;
  }

  public function getTokenText(&$token) {
    return $token->getText($this->astNode->fileContents);
  }

  public function getTokenFullText(&$token) {
    return $token->getFullText($this->astNode->fileContents);
  }

  public function getLeadingText(&$token) {
    return $token->getLeadingCommentsAndWhitespaceText($this->astNode->fileContents);
  }

  public function getSource(int $start, $length = null) {
    if (is_null($length)) {
      return substr($this->astNode->fileContents, $start);
    }
    return substr($this->astNode->fileContents, $start, $length);
  }

  public function getLineOffsets() {
    if (!isset($this->sourceLineOffsets)) {
      // generate source line offset caches
      $this->sourceLineOffsets = self::getSourceLineStartIndices($this->astNode->fileContents);
    }
    return $this->sourceLineOffsets;
  }

  public function getLocation(int $pos) {
    if (!isset($this->sourceLineOffsets)) {
      $this->getLineOffsets();
    }

    return self::getSourceLocation($pos, $this->sourceLineOffsets);
  }

  public static function getSourceLineStartIndices(string $source) {
    $lines = explode("\n", $source);
    $length = 0;
    $lineStartIndices = [ $length ];
    foreach ($lines as $line) {
      $length += strlen($line) + 1;
      $lineStartIndices []= $length;
    }
    return $lineStartIndices;
  }

  public static function getSourceLocation(int $pos, array $lineStartIndices) {
    $line = 0;
    $column = 0;
    $length = 0;
    for ($i = 0; $i < count($lineStartIndices) - 1; $i++) {
      $offset = $lineStartIndices[$i];
      if ($pos >= $offset && $pos < $lineStartIndices[$i + 1]) {
        $line = $i + 1;
        $column = $pos - $offset + 1;
        break;
      }
    }
    return [ 'line' => $line, 'column' => $column ];
  }

  public static function getSourcePosition($loc, array $lineStartIndices) {
    return $lineStartIndices[$loc['line'] - 1] + $loc['column'] - 1;
  }

  public static function isWhiteSpaces($str) {
    return preg_match('/^\s+$/', $str, $matches);
  }

}

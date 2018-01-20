<?php

class SourceCode {

  public $astNode;
  private $sourceLineOffsets;

  function __construct($astNode) {
    $this->astNode = $astNode;
  }

  public function getTokenText(&$token) {
    return $token->getText($this->astNode->fileContents);
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
      $lines = explode("\n", $this->astNode->fileContents);
      $length = 0;
      $this->sourceLineOffsets = [ $length ];
      foreach ($lines as $line) {
        $length += strlen($line) + 1;
        $this->sourceLineOffsets []= $length;
      }
    }
    return $this->sourceLineOffsets;
  }

  public function getLocation(int $pos) {
    if (!isset($this->sourceLineOffsets)) {
      $this->getLineOffsets();
    }

    $line = 0;
    $column = 0;
    $length = 0;
    for ($i = 0; $i < count($this->sourceLineOffsets) - 1; $i++) {
      $offset = $this->sourceLineOffsets[$i];
      if ($pos >= $offset && $pos < $this->sourceLineOffsets[$i + 1]) {
        $line = $i + 1;
        $column = $pos - $offset + 1;
        break;
      }
    }
    return [ 'line' => $line, 'column' => $column ];
  }

}

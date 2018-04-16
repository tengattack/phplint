<?php

use Microsoft\PhpParser\TokenKind;

class HTMLRule extends Rule {

  private $_html = '';
  private $_htmlMasks = [];
  private $_style = '';
  private $_styleMasks = [];
  private $opts = [
    'eatNewLine' => false,
    'styleIndent' => 2,
  ];

  public function filters() {
    return [
      'InlineHtml',
      'EndOfFileToken',
    ];
  }

  protected function makeOptions() {
    if (!empty($this->options)) {
      $opt = $this->options[0];
      if (is_array($opt)) {
        foreach ($this->opts as $key => $_) {
          if (array_key_exists($key, $opt)) {
            $this->opts[$key] = $opt[$key];
          }
        }
      }
    }
  }

  protected function pluralize(string $word, int $count): string {
    return ($count === 1 ? $word : "{$word}s");
  }

  protected function getWhiteSpaceLength($type, $str) {
    if ($type === 'suffix') {
      $regex = '/[ \t]+$/';
    } else {
      $regex = '/^[ \t]+/';
    }
    if (preg_match($regex, $str, $matches)) {
      return strlen($matches[0]);
    }
    return 0;
  }

  private function reset() {
    $this->_html = '';
    $this->_htmlMasks = [];
    $this->_style = '';
    $this->_styleMasks = [];
  }

  private function reportHTML($issue, $pos) {
    $originalPos = $this->getOriginalPosition($pos, $this->_htmlMasks);
    $this->context->report($this->id . '/' . $issue['rule'], $this->severity,
      null, $originalPos, $issue['message']);
  }

  private function appendHTML($html, $start, $end) {
    // var_dump([ $html, $start, $end ]);
    $this->_html .= $html;
    $this->_htmlMasks []= [
      'start' => $start,
      'end' => $end,
    ];
  }

  private function getOriginalPosition(int $pos, array $masks) {
    $length = 0;
    $originalPos = 0;
    foreach ($masks as $mask) {
      if ($pos > $length) {
        $originalPos = $mask['start'] + $pos - $length;
      } else {
        break;
      }
      $length += $mask['end'] - $mask['start'];
    }
    return $originalPos;
  }

  private function reportStyle($issue, $pos) {
    $htmlPos = $this->getOriginalPosition($pos, $this->_styleMasks);
    $originalPos = $this->getOriginalPosition($htmlPos, $this->_htmlMasks);
    if (gettype($issue) === 'string') {
      $this->context->report($this->id . '/style', $this->severity, null, $originalPos, $issue);
    } else {
      $this->context->report($this->id . '/style/' . $issue['linter'], $this->severity,
        null, $originalPos, $issue['reason']);
    }
  }

  private function appendStyle($style, $start) {
    $this->_style .= $style;
    $this->_styleMasks []= [
      'start' => $start,
      'end' => $start + strlen($style),
    ];
  }

  private function checkStyle($htmlLineOffsets) {
    $styleIndentType = $this->opts['styleIndent'] === 'tab' ? 'tab' : 'space';
    $styleIndent = $styleIndentType === 'tab' ? 1 : (int)$this->opts['styleIndent'];
    $stylePos = 0;

    while (preg_match('/<style.*?>(.*?)<\/style>/s', $this->_html, $matches, PREG_OFFSET_CAPTURE, $stylePos)) {
      $pos = $matches[0][1];
      $start = SourceCode::getSourceLocation($pos, $htmlLineOffsets);
      $baseIndent = $start['column'] - 1;
      $stylePos = $pos + strlen($matches[0][0]);
      $lines = explode("\n", $matches[1][0]);
      if (!empty($lines)) {
        $pos = $matches[1][1];
        if ($lines[0]) {
          // check if style content whether start in new line
          if (!SourceCode::isWhiteSpaces($lines[0])) {
            // report
            $lineStart = $this->getWhiteSpaceLength('prefix', $lines[0]);
            $this->appendStyle(substr($lines[0], $lineStart) . "\n", $pos + $lineStart);
            $this->reportHTML([
              'rule' => 'style',
              'message' => 'Style should starts with new line',
            ], $pos);
          }
        }
        $pos += strlen($lines[0]) + 1;
        for ($i = 1; $i < count($lines) - 1; $i++) {
          $lineLength = strlen($lines[$i]);
          if ($lineLength <= 0 || ($lineLength === 1 && $lines[$i] === "\r")) {
            $this->appendStyle($lines[$i] . "\n", $pos);
            $pos += $lineLength + 1;
            continue;
          }
          $indentStr = substr($lines[$i], 0, $baseIndent + $styleIndent);
          $lineStart = $this->checkStyleIndent(
            $styleIndentType, $baseIndent + $styleIndent, $indentStr, $pos);
          $this->appendStyle(substr($lines[$i], $lineStart) . "\n", $pos + $lineStart);
          $pos += $lineLength + 1;
        }
        if ($i >= 1) {
          if (!SourceCode::isWhiteSpaces($lines[$i])) {
            // report
            $indentStr = substr($lines[$i], 0, $baseIndent + $styleIndent);
            $lineStart = $this->checkStyleIndent(
              $styleIndentType, $baseIndent + $styleIndent, $indentStr, $pos);
            $this->appendStyle(substr($lines[$i], $lineStart), $pos + $lineStart);
            $this->reportHTML([
              'rule' => 'style',
              'message' => 'Style should ends with new line',
            ], $pos + strlen($lines[$i]));
          }
        }
      }
    }

    $cmd = sprintf('scss-lint --format=JSON --stdin-file-path="%s"',
      $this->context->sourceCode->filePath);

    $issueList = $this->runCommand('scss-lint', $cmd, $this->_style);
    if ($issueList) {
      $styleLineOffsets = SourceCode::getSourceLineStartIndices($this->_style);
      // key-value style issue list
      foreach ($issueList as $issues) {
        foreach ($issues as $issue) {
          $issue['position'] = SourceCode::getSourcePosition($issue, $styleLineOffsets);
          $this->reportStyle($issue, $issue['position']);
        }
      }
    }
  }

  private function checkStyleIndent($type, $count, $indentText, $pos) {
    $actualSpaces = 0;
    $actualTabs = 0;
    $len = strlen($indentText);
    for ($i = 0; $i < $len; $i++) {
      if ($indentText[$i] === ' ') {
        $actualSpaces++;
      } elseif ($indentText[$i] === "\t") {
        $actualTabs++;
      } else {
        // report
        break;
      }
    }

    $errorMessage = null;
    if ($type === 'tab' && $actualTabs < $count) {
      $expectedStatement = "$count " . $this->pluralize($type, $count);
      $errorMessage = "Expected indentation of $expectedStatement at least but found $actualTabs";
    } elseif ($type === 'space' && $actualSpaces < $count) {
      $expectedStatement = "$count " . $this->pluralize($type, $count);
      $errorMessage = "Expected indentation of $expectedStatement at least but found $actualSpaces";
    }

    if ($errorMessage) {
      $this->reportHTML([
        'rule' => 'style',
        'message' => $errorMessage,
      ], $pos);
    }

    return $i;
  }

  private function checkHTML() {
    if (!$this->_html) {
      return;
    }

    $htmlLineOffsets = SourceCode::getSourceLineStartIndices($this->_html);
    // parse & check style first
    $this->checkStyle($htmlLineOffsets);

    $cmd = sprintf('htmllint --format=json --stdin-file-path="%s"',
      $this->context->sourceCode->filePath);

    $issueList = $this->runCommand('htmllint', $cmd, $this->_html);
    if ($issueList) {
      // only one file was provided
      $result = $issueList[0];
      foreach ($result['messages'] as $issue) {
        $issue['position'] = SourceCode::getSourcePosition($issue, $htmlLineOffsets);
        $this->reportHTML($issue, $issue['position']);
      }
    }
  }

  private function runCommand($program, $command, $stdin) {
    $desc = [
      0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
      1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
      2 => ['pipe', 'w'],  // stderr
    ];

    $process = proc_open($command, $desc, $pipes);

    if (!is_resource($process)) {
      throw new Exception('proc_open ' . $program . ' command failed');
    }

    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout

    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);

    $result = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $ret = proc_close($process);
    if ($stderr) {
      throw new Exception("{$program} error:\n{$stderr}\nexit code: {$ret}");
    }

    $data = json_decode($result, true);
    if (!$data && $ret !== 0) {
      throw new Exception("{$program} error:\n{$result}");
    }

    return $data;
  }

  public function InlineHtml(&$node) {
    $newlineText = '';
    $start = -1;
    $end = -1;
    foreach ($node->getChildTokens() as $token) {
      if ($token->kind === TokenKind::ScriptSectionEndTag) {
        if ($this->opts['eatNewLine']) {
          continue;
        }
        $endText = $this->context->sourceCode->getTokenText($token);
        if (substr($endText, -1) === "\n") {
          // keep newline
          $end = $token->fullStart + $token->length;
          if (substr($endText, -2, 1) === "\r") {
            $newlineText .= "\r\n";
            if ($start === -1) {
              $start = $end - 2;
            }
          } else {
            $newlineText .= "\n";
            if ($start === -1) {
              $start = $end - 1;
            }
          }
        }
      } elseif ($token->kind === TokenKind::InlineHtml) {
        $text = $this->context->sourceCode->getTokenText($token);
        $end = $token->fullStart + $token->length;
        if ($newlineText) {
          $text = $newlineText . $text;
        } else {
          $start = $token->fullStart;
        }
        $this->appendHTML($text, $start, $end);
        $newlineText = '';
        $start = -1;
      } elseif ($newlineText) {
        $this->appendHTML($newlineText, $start, $end);
        $newlineText = '';
        $start = -1;
      }
    }
    if ($newlineText) {
      $this->appendHTML($newlineText, $start, $end);
    }
  }

  public function EndOfFileToken(&$token) {
    $this->checkHTML();
    $this->reset();
  }

}

return 'HTMLRule';

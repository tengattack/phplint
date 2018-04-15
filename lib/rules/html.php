<?php

use Microsoft\PhpParser\TokenKind;

class HTMLRule extends Rule {

  private $_html = '';
  private $_htmlMasks = [];
  private $_stylePos = 0;
  private $opts = [
    'eatNewLine' => false,
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

  private function reset() {
    $this->_html = '';
    $this->_htmlMasks = [];
    $this->_stylePos = 0;
  }

  private function reportHTML($issue, $pos) {
    $this->context->report($this->id . '/' . $issue['rule'], $this->severity,
      null, $pos, $issue['message']);
  }

  private function appendHTML($html, $start, $end) {
    // var_dump([ $html, $start, $end ]);
    $this->_html .= $html;
    $this->_htmlMasks []= [
      'start' => $start,
      'end' => $end,
    ];
    $this->checkStyle();
  }

  private function getOriginalPosition(int $htmlPos) {
    $length = 0;
    $pos = 0;
    foreach ($this->_htmlMasks as $mask) {
      if ($htmlPos > $length) {
        $pos = $mask['start'] + $htmlPos - $length;
      } else {
        return $pos;
      }
      $length += $mask['end'] - $mask['start'];
    }
    return $length;
  }

  private function checkStyle() {
    while (preg_match('/<style.*?>(.*?)<\/style>/s', $this->_html, $matches, PREG_OFFSET_CAPTURE, $this->_stylePos)) {
      $this->_stylePos = $matches[0][1] + strlen($matches[0][0]);
    }
  }

  private function checkHTML() {
    if (!$this->_html) {
      return;
    }

    $cmd = sprintf('htmllint --format=json --stdin-file-path="%s"',
      $this->context->sourceCode->filePath);

    $desc = [
      0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
      1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
      2 => ['pipe', 'w'],  // stderr
    ];

    $process = proc_open($cmd, $desc, $pipes);

    if (!is_resource($process)) {
      throw new Exception('proc_open htmllint command failed');
    }

    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout

    fwrite($pipes[0], $this->_html);
    fclose($pipes[0]);

    $result = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $ret = proc_close($process);
    if ($stderr) {
      throw new Exception("htmllint error:\n" . $stderr . "\nexit code: " . $ret);
    }

    $issueList = json_decode($result, true);
    if ($issueList) {
      $htmlLineOffsets = SourceCode::getSourceLineStartIndices($this->_html);
      // only one file was provided
      $result = $issueList[0];
      foreach ($result['messages'] as $issue) {
        $issue['position'] = SourceCode::getSourcePosition($issue, $htmlLineOffsets);
        $originalPos = $this->getOriginalPosition($issue['position']);
        $this->reportHTML($issue, $originalPos);
      }
    } else {
      throw new Exception("htmllint error:\n" . $result);
    }
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

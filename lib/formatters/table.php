<?php

function pluralize(string $word, int $count): string {
  return ($count === 1 ? $word : "{$word}s");
}

function formatter_table_render($results) {
  $output = '';
  foreach ($results as $result) {
    if (empty($result->messages)) {
      continue;
    }

    $table = new cli\Table();
    $table->setHeaders(['loc', 'severity', 'message', 'ruleId']);

    $output .= $result->filePath . ":\n";
    foreach ($result->messages as $message) {
      $table->addRow([
        $message['line'] . ':' . $message['column'],
        Rule::severityToString($message['severity']),
        $message['message'],
        $message['ruleId'],
      ]);
    }

    $output .= implode("\n", $table->getDisplayLines());
    $output .= "\n";
  }

  return $output;
}

return 'formatter_table_render';

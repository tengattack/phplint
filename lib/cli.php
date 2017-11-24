<?php

define('ROOT', dirname(__DIR__));

// Autoload required classes
require ROOT . "/vendor/autoload.php";
require_once ROOT . '/lib/rule.php';
require_once ROOT . '/lib/parser.php';

$fileName = '';
for ($i = 1; $i < count($argv); $i++) {
  switch ($argv[$i]) {
  case '-v':
    define('VERBOSE', 1);
    break;
  default:
    $fileName = $argv[$i];
  }
}
if (!$fileName) {
  echo 'Missing target php file';
  exit;
}

$config = yaml_parse_file(ROOT . '/phplint.yml');

if (!array_key_exists('rules', $config) || empty($config['rules'])) {
  echo 'phplint.yaml do not contains rules options';
  exit;
}

$rules = [];
foreach ($config['rules'] as $rule => $level) {
  $rules[$rule] = $level;
}

$source = file_get_contents($fileName);
$report = processText($source, $rules);

echo json_encode($report->getMessages(), JSON_UNESCAPED_UNICODE);

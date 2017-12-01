<?php

define('ROOT', dirname(__DIR__));

// Autoload required classes
require ROOT . "/vendor/autoload.php";
require_once ROOT . '/lib/rule.php';
require_once ROOT . '/lib/parser.php';

function errorAndExit($error) {
  echo $error . "\n";
  exit(1);
}

$fileName = '';
$configFile = ROOT . '/phplint.yml';
for ($i = 1; $i < count($argv); $i++) {
  switch ($argv[$i]) {
  case '-v':
    define('VERBOSE', 1);
    break;
  case '-c':
    if ($i < count($argv) - 1) {
      $i++;
      $configFile = $argv[$i];
    } else {
      errorAndExit('Missing config file');
    }
    break;
  default:
    $fileName = $argv[$i];
  }
}
if (!$fileName) {
  errorAndExit('Missing target php file');
}

if (!file_exists($configFile)) {
  errorAndExit('Config file not exists');
}

$config = yaml_parse_file($configFile);

if (!array_key_exists('rules', $config) || empty($config['rules'])) {
  errorAndExit('Config file do not contains rules options');
}

$rules = [];
foreach ($config['rules'] as $rule => $level) {
  $rules[$rule] = $level;
}

$source = file_get_contents($fileName);
$report = processText($source, $rules);

echo json_encode($report->getMessages(), JSON_UNESCAPED_UNICODE);

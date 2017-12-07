<?php

define('ROOT', dirname(__DIR__));

// Autoload required classes
if (file_exists(ROOT . '/vendor/autoload.php')) {
  require ROOT . "/vendor/autoload.php";
} else {
  require ROOT . "/../../autoload.php";
}
require_once ROOT . '/lib/rule.php';
require_once ROOT . '/lib/parser.php';
require_once ROOT . '/lib/formatter.php';

function errorAndExit($error) {
  echo $error . "\n";
  exit(1);
}

$fileName = '';
$formatter = 'table';
$configFile = ROOT . '/phplint.yml';
for ($i = 1; $i < count($argv); $i++) {
  switch ($argv[$i]) {
  case '-v':
    define('VERBOSE', 1);
    break;
  case '-f':
    if ($i < count($argv) - 1) {
      $i++;
      $formatter = $argv[$i];
    } else {
      errorAndExit('Missing formatter param');
    }
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
if (!file_exists($fileName)) {
  errorAndExit('Target file not exists');
}
if (!Formatter::exists($formatter)) {
  errorAndExit("Specified formatter '$formatter' not found");
}

$config = yaml_parse_file($configFile);

if (!array_key_exists('rules', $config) || empty($config['rules'])) {
  errorAndExit('Config file do not contains rules options');
}

$rules = [];
foreach ($config['rules'] as $rule => $level) {
  $rules[$rule] = $level;
}

$report = processFile($fileName, $rules);

$format = new Formatter($formatter, [ $report ]);
echo $format->render();

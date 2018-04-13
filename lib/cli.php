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

function printUsage() {
  echo implode("\n", [
    'phplint [options] file.php',
    '',
    'Basic configuration:',
    '  -h, --help                     Show this help',
    '  -c, --config path::String      Use configuration from this file or shareable config',
    '  --verbose [level=1]            Verbose mode',
    '',
    'Output:',
    '  -f, --format String            Use a specific output format - default: table',
    '',
  ]);
}

if (count($argv) < 2) {
  printUsage();
  exit(0);
}

$fileName = '';
$formatter = 'table';
$configFile = '.phplint.yml';
$userConfigFile = false;
for ($i = 1; $i < count($argv); $i++) {
  switch ($argv[$i]) {
  case '-h':
  case '--help':
    printUsage();
    exit(0);
    break;
  case '--verbose':
    $verbose = 1;
    if ($i < count($argv) - 1) {
      if (is_numeric($argv[$i + 1])) {
        $i++;
        $verbose = (int)$argv[$i];
      }
    }
    define('VERBOSE', $verbose);
    break;
  case '-f':
  case '--format':
    if ($i < count($argv) - 1) {
      $i++;
      $formatter = $argv[$i];
    } else {
      errorAndExit('Missing formatter param');
    }
    break;
  case '-c':
  case '--config':
    if ($i < count($argv) - 1) {
      $i++;
      $configFile = $argv[$i];
      $userConfigFile = true;
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
  if ($userConfigFile) {
    errorAndExit('Config file not exists');
  }
  $configFile = ROOT . '/.phplint.yml';
  if (!file_exists($configFile)) {
    errorAndExit('Default config file not exists');
  }
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

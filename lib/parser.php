<?php

require_once ROOT . '/lib/context.php';
require_once ROOT . '/lib/source-code.php';
require_once ROOT . '/lib/traverse.php';

use Microsoft\PhpParser\{DiagnosticsProvider, Parser};

function parseSourceCode(string &$source) {
  $parser = new Parser(); # instantiates a new parser instance
  $astNode = $parser->parseSourceFile($source); # returns an AST from string contents
  // $errors =  DiagnosticsProvider::getDiagnostics($astNode); # get errors from AST Node (as a Generator)
  return $astNode;
}

function processSource(string $source, &$rules, string $fileName = '') {
  $astNode = parseSourceCode($source);

  $sourceCode = new SourceCode($astNode, $fileName);
  $context = new Context($sourceCode);
  $context->loadRules($rules);

  $traverse = new Traverse($sourceCode);
  $traverse->walk($context);

  return $context->getReport($fileName);
}

function processFile(string $fileName, &$rules) {
  $source = file_get_contents($fileName);
  return processSource($source, $rules, $fileName);
}

<?php

require_once ROOT . '/lib/context.php';
require_once ROOT . '/lib/traverse.php';

use Microsoft\PhpParser\{DiagnosticsProvider, Parser};

function parseSourceCode(string &$source) {
  $parser = new Parser(); # instantiates a new parser instance
  $astNode = $parser->parseSourceFile($source); # returns an AST from string contents
  // $errors =  DiagnosticsProvider::getDiagnostics($astNode); # get errors from AST Node (as a Generator)
  return $astNode;
}

function processText(string $source, $rules) {
  $astNode = parseSourceCode($source);

  $context = new Context($astNode);
  $context->loadRules($rules);

  $traverse = new Traverse($astNode);
  $traverse->walk($context);

  return $context->getReport();
}

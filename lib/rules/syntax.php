<?php

use Microsoft\PhpParser\Diagnostic;
use Microsoft\PhpParser\DiagnosticsProvider;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\Node\SourceFileNode;

class LintDiagnostic extends Diagnostic {

    /** @var Node|Token */
    public $node;

    public function __construct($node, Diagnostic $diagnostic) {
        $this->kind = $diagnostic->kind;
        $this->message = $diagnostic->message;
        $this->start = $diagnostic->start;
        $this->length = $diagnostic->length;
        // keep current node
        $this->node = $node;
    }

}

class LintDiagnosticsProvider extends DiagnosticsProvider {

    public static function getDiagnostics(Node $n) : array {
        $diagnostics = [];

        foreach ($n->getDescendantNodesAndTokens() as $node) {
            if (($diagnostic = self::checkDiagnostics($node)) !== null) {
                $diagnostics[] = new LintDiagnostic($node, $diagnostic);
            }
        }

        return $diagnostics;
    }

}

class SyntaxRule extends Rule {

    public function filters() {
        return [
            'Program',
        ];
    }

    public function Program(&$node) {
        if ($node instanceof SourceFileNode) {
            $errors = LintDiagnosticsProvider::getDiagnostics($node);
            foreach ($errors as $err) {
                $this->report($err->node, $err->start, $err->message);
            }
        }
    }

}

Rule::register(__FILE__, 'SyntaxRule');

<?php

use Microsoft\PhpParser\TokenKind;

class FuncColonSpacingRule extends Rule {

    private $spacedBefore = false;
    private $spacedAfter = true;

    protected function makeOptions() {
        if (!empty($this->options)) {
            $opt = $this->options[0];
            if (array_key_exists('before', $opt)) {
                $this->spacedBefore = !!$opt['before'];
            }
            if (array_key_exists('after', $opt)) {
                $this->spacedAfter = !!$opt['after'];
            }
        }
    }

    public function filters() {
        return [
            'FunctionDeclaration',
        ];
    }

    private function reportNoBeginningSpace(&$token) {
        $tokenValue = $this->getTokenText($token);
        $this->report($token, "There should be no space after '$tokenValue'.");
    }

    private function reportNoEndingSpace(&$token) {
        $tokenValue = $this->getTokenText($token);
        $this->report($token, "There should be no space before '$tokenValue'.");
    }

    private function reportRequiredBeginningSpace(&$token) {
        $tokenValue = $this->getTokenText($token);
        $this->report($token, "A space is required after '$tokenValue'.");
    }

    private function reportRequiredEndingSpace(&$token) {
        $tokenValue = $this->getTokenText($token);
        $this->report($token, "A space is required before '$tokenValue'.");
    }

    public function check(&$node) {
        foreach ($node->getChildTokens() as $token) {
            if ($token->kind === TokenKind::ColonToken) {
                // before colon
                $hasSpace = $this->isSpaceBeforeToken($token, $this->spacedBefore);
                if ($hasSpace && !$this->spacedBefore) {
                    $this->reportNoEndingSpace($token);
                } elseif (!$hasSpace && $this->spacedBefore) {
                    $this->reportNoEndingSpace($token);
                }

                $nextToken = $this->getNextToken($node, $token);
                if ($nextToken) {
                    // after colon
                    $hasSpace = $this->isSpaceBeforeToken($nextToken, $this->spacedAfter);
                    if ($hasSpace && !$this->spacedAfter) {
                        $this->reportNoBeginningSpace($token);
                    } elseif (!$hasSpace && $this->spacedAfter) {
                        $this->reportRequiredBeginningSpace($token);
                    }
                }
                break;
            }
        }
    }

    public function FunctionDeclaration(&$node) {
        $this->check($node);
    }

}

Rule::register(__FILE__, 'FuncColonSpacingRule');

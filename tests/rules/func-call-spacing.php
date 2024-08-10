<?php

final class FuncCallSpacingRuleTest extends RuleTestCase {

    public $ruleId = 'func-call-spacing';

    public function testDefault() {
        $source = <<<EOF
<?php
function test() {
    foo();
    foo ();
    return false;
}
EOF;
        $rules = ['func-call-spacing' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[4, 9]], $report);
    }

    public function testConfig() {
        $source = <<<EOF
<?php
function test() {
    foo();
    foo ();
    return false;
}
EOF;
        $rules = ['func-call-spacing' => ['error', 'always']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[3, 8]], $report);
    }
}

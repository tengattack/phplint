<?php

final class FuncColonSpacingRuleTest extends RuleTestCase {

    public $ruleId = 'func-colon-spacing';

    public function testDefault() {
        $source = <<<EOF
<?php
function test() : bool {
    return false;
}
EOF;
        $rules = ['func-colon-spacing' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[2, 17]], $report);

        $source = <<<EOF
<?php
function test():bool {
    return false;
}
EOF;
        $report = processSource($source, $rules);
        $this->assertLineColumn([[2, 16]], $report);
    }

    public function testConfig() {
        $source = <<<EOF
<?php
function test() : bool {
    return false;
}
EOF;
        $rules = ['func-colon-spacing' => ['error', ['before' => true, 'after' => true]]];
        $report = processSource($source, $rules);
        $this->assertLineColumn([], $report);

        $source = <<<EOF
<?php
function test():bool {
    return false;
}
EOF;
        $report = processSource($source, $rules);
        $this->assertLineColumn([[2, 16], [2, 16]], $report);
    }
}

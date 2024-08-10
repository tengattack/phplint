<?php

final class SwitchColonSpacingRuleTest extends RuleTestCase {

    public $ruleId = 'switch-colon-spacing';

    public function testDefault() {
        $source = <<<EOF
<?php
function test() {
    switch (\$a) {
        case 1:
            break;
    }
    return false;
}
EOF;
        $rules = ['switch-colon-spacing' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([], $report);

        $source = <<<EOF
<?php
function test() {
    switch (\$a) {
        case 1 :
            break;
    }
    return false;
}
EOF;
        $report = processSource($source, $rules);
        $this->assertLineColumn([[4, 15]], $report);
    }
}

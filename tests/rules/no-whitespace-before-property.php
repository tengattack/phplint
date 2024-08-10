<?php

final class NoWhitespaceBeforePropertyRuleTest extends RuleTestCase {

    public $ruleId = 'no-whitespace-before-property';

    public function testDefault() {
        $source = <<<EOF
<?php
function test() {
    echo \$foo->bar;
    echo \$foo::\$bar;
    return false;
}
EOF;
        $rules = ['no-whitespace-before-property' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([], $report);

        $source = <<<EOF
<?php
function test() {
    echo \$foo ->bar;
    echo \$foo ::\$bar;
    return false;
}
EOF;
        $report = processSource($source, $rules);
        $this->assertLineColumn([[3, 14], [4, 14]], $report);
    }
}

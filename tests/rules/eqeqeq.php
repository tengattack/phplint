<?php

final class EqeqeqRuleTest extends RuleTestCase {

    public $ruleId = 'eqeqeq';
    private $source = <<<EOF
<?php
if (\$a == 1) {
    echo \$a;
}
EOF;

    public function testDefault() {
        $rules = ['eqeqeq' => ['error']];
        $report = processSource($this->source, $rules);

        $this->assertLineColumn([[2, 8]], $report);
    }
}

<?php

final class SyntaxRuleTest extends RuleTestCase {

    public $ruleId = 'syntax';

    public function testDefault() {
        $source = <<<EOF
<?php
if (\$a && in_array(1, \$a) {
    echo 1;
}
EOF;
        $rules = ['syntax' => ['error']];
        $report = processSource($source, $rules);
        $this->assertContainsMessage("')' expected.", $report);
    }
}

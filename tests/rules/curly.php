<?php

final class CurlyRuleTest extends RuleTestCase {

    public $ruleId = 'curly';
    private $source = <<<EOF
<?php
if (\$a) {
    echo \$a;
}
if (\$b) echo \$b;
if (\$c)
    echo \$c;
EOF;

    public function testDefault() {
        $rules = ['curly' => ['error']];
        $report = processSource($this->source, $rules);

        $this->assertLineColumn([[5, 8], [6, 8]], $report);
    }

    public function testMultiOnly() {
        $rules = ['curly' => ['error', 'multi']];
        $report = processSource($this->source, $rules);

        $this->assertLineColumn([[6, 8]], $report);
    }
}

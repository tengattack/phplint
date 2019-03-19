<?php

final class EOLLastRuleTest extends RuleTestCase {

    public $ruleId = 'eol-last';
    private $source1 = "<?php\necho \$a;\n";
    private $source2 = "<?php\necho \$a;";

    public function testDefault() {
        $rules = ['eol-last' => ['error']];
        $report = processSource($this->source1, $rules);
        $this->assertLineColumn([[2, 9]], $report);

        $source2 = "<?php\necho \$a;";
        $report = processSource($this->source2, $rules);
        $this->assertLineColumn([], $report);
    }

    public function testAlways() {
        $rules = ['eol-last' => ['error', 'always']];
        $report = processSource($this->source1, $rules);
        $this->assertLineColumn([], $report);

        $report = processSource($this->source2, $rules);
        $this->assertLineColumn([[2, 9]], $report);
    }

    public function testEmpty() {
        $rules = ['eol-last' => ['error', 'always']];
        $source = '';

        $report = processSource($source, $rules);
        $this->assertLineColumn([], $report);
    }
}

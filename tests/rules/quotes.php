<?php

final class QuotesRuleTest extends RuleTestCase {

    public $ruleId = 'quotes';
    private $source = <<<EOF
<?php
\$a = 'foo';
\$b = "bar";
\$c = 'foo"bar"';
echo "bar'a'";
echo "bar\$a";
echo "bar\\n";
EOF;

    public function testDefault() {
        // case 1 (default)
        $rules = ['quotes' => ['error']];
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[3, 5]], $report);

        // case 2 (default quote option is single)
        $rules = ['quotes' => ['error', ['avoidEscape' => false]]];
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[3, 5], [5, 5], [7, 5]], $report);
    }

    public function testSingle() {
        // case 1
        $rules = ['quotes' => ['error', 'single', [
            'avoidEscape' => false,
            'allowTemplateLiterals' => false,
        ]]];
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[3, 5], [5, 5], [6, 5], [7, 5]], $report);

        // case 2
        $rules['quotes'][2]['avoidEscape'] = true;
        $rules['quotes'][2]['allowTemplateLiterals'] = false;
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[3, 5], [6, 5]], $report);

        // case 3
        $rules['quotes'][2]['avoidEscape'] = false;
        $rules['quotes'][2]['allowTemplateLiterals'] = true;
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[3, 5], [5, 5], [7, 5]], $report);

        // case 4 (default)
        $rules['quotes'][2]['avoidEscape'] = true;
        $rules['quotes'][2]['allowTemplateLiterals'] = true;
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[3, 5]], $report);
    }

    public function testDouble() {
        // case 1
        $rules = ['quotes' => ['error', 'double']];
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[2, 5]], $report);

        // case 2
        $rules = ['quotes' => ['error', 'double', [
            'avoidEscape' => false,
            'allowTemplateLiterals' => false,
        ]]];
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[2, 5], [4, 5]], $report);

        // case 3
        $rules['quotes'][2]['avoidEscape'] = false;
        $rules['quotes'][2]['allowTemplateLiterals'] = true;
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[2, 5], [4, 5]], $report);

        // case 4
        $rules['quotes'][2]['avoidEscape'] = true;
        $rules['quotes'][2]['allowTemplateLiterals'] = true;
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[2, 5]], $report);

        // case 5 (no effects for allowTemplateLiterals when double quote)
        $rules['quotes'][2]['avoidEscape'] = true;
        $rules['quotes'][2]['allowTemplateLiterals'] = false;
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[2, 5]], $report);
    }
}

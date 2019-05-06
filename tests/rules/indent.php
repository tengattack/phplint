<?php

final class IndentRuleTest extends RuleTestCase {

    public $ruleId = 'indent';

    public function testDefault() {
        $source = <<<EOF
<?php
\$a = 'foo
bar';
if (\$a) {
echo \$a;
}
EOF;
        // case 1 (default)
        $rules = ['indent' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[3, 1], [5, 1]], $report);
    }

    public function testString() {
        $source = <<<EOF
<?php
\$a = 'foo
bar';
\$b = "foo
bar";
\$c = "foo\$a
bar
";
\$d = <<<EOT
    foo
bar
   baz
EOT;
EOF;
        $rules = ['indent' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[3, 1], [5, 1], [7, 1], [8, 1]], $report);
    }

    public function testFunctionUse()
    {
        // wrong indent
        $source = <<<EOF
<?php
\$array = [
    4,
    5,
];
\$a = 1;
array_map(function (\$v)
    use (\$a) {
        \$a = \$a + 1;
    return \$v + \$a;
}, \$array);
EOF;
        $rules = ['indent' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[8, 1], [9, 1]], $report);

        // correct indent
        $source = <<<EOF
<?php
\$array = [
    4,
    5,
];
\$a = 1;
array_map(function (\$v)
        use (\$a) {
    \$a = \$a + 1;
    return \$v + \$a;
}, \$array);
EOF;
        $rules = ['indent' => ['error']];
        $report = processSource($source, $rules);
        $this->assertEmpty($report->getResult()->messages);
    }

}

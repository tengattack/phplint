<?php

final class NoLonelyIfRuleTest extends RuleTestCase {

    public $ruleId = 'no-lonely-if';
    private $source = <<<EOF
<?php
if (\$a) {
    echo \$a;
} else {
    if (\$b) {
        echo \$b;
    }
}
if (\$a) {
    echo \$a;
} else {
    {
        if (\$b) {
            echo \$b;
        }
    }
}
if (\$a) {
    echo \$a;
} else {
    if (\$b) {
        echo \$b;
    }
    echo \$a . \$b;
}
if (\$a) {
    echo \$a;
} else {
    if (\$b) {
        echo \$b;
    }
    if (\$c) {
        echo \$c;
    }
}
if (\$a) {
    echo \$a;
} elseif (\$b) {
    echo \$b;
}
EOF;

    public function testDefault() {
        $rules = ['no-lonely-if' => ['error']];
        $report = processSource($this->source, $rules);
        $this->assertLineColumn([[5, 5], [13, 9]], $report);
    }

}

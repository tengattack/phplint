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

namespace app\commands;

    use GuzzleHttp\Client;  // line 5: NamespaceUseDeclaration
use GuzzleHttp\Exception\ClientException;

class ToolsController extends Controller
{
        use SebastianBergmann\Diff\Utils\UnifiedDiffAssertTrait;  // line 10: TraitUseClause
    use PHPUnit\ExampleExtension\TestCaseTrait;

    public function actionToJson()
    {
        \$array = [
            4,
            5,
        ];
        \$ab = 1;
        \$cd = 1;
        \$ef = 1;
        \$array1 = array_map(function (\$v) use (\$ab,
                \$cd, \$ef) {
            return \$v + \$ab;
        }, \$array);

        \$array2 = array_map(function (\$v)
            use (\$ab, \$cd, \$ef) {  // line 28: AnonymousFunctionUseClause
            return \$v + \$ab;
        }, \$array);
    }

}
EOF;
        $rules = ['indent' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[5, 1], [10, 1], [28, 1]], $report);

        // correct indent
        $source = <<<EOF
<?php

namespace app\commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ToolsController extends Controller
{
    use SebastianBergmann\Diff\Utils\UnifiedDiffAssertTrait;
    use PHPUnit\ExampleExtension\TestCaseTrait;

    public function actionToJson()
    {
        \$array = [
            4,
            5,
        ];
        \$ab = 1;
        \$cd = 1;
        \$ef = 1;
        \$array1 = array_map(function (\$v) use (\$ab,
                \$cd, \$ef) {
            return \$v + \$ab;
        }, \$array);

        \$array2 = array_map(function (\$v)
                use (\$ab, \$cd, \$ef) {
            return \$v + \$ab;
        }, \$array);
    }

}
EOF;
        $rules = ['indent' => ['error']];
        $report = processSource($source, $rules);
        $this->assertLineColumn([], $report);
    }

}

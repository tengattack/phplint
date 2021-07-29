<?php

final class HtmlRuleTest extends RuleTestCase {

    public static $htmllintrc = <<<EOF
{
    "maxerr": false,
    "raw-ignore-regex": "/<(script|style)[\\\\s\\\\S]*?>[\\\\s\\\\S]*?<\\\\/\\\\1>/i"
}
EOF;

    public static $stylelintrc = <<<EOF
rules:
  color-named: never
EOF;

    public $ruleId = 'html';

    private $source = <<<EOF
<html>
<head>
<style>
  .red {
    color: red;
  }
</style>
</head>
<body>
<?php
echo 'test';
?>
</body>
</html>
EOF;

    public static function setUpBeforeClass() {
        file_put_contents('.htmllintrc', self::$htmllintrc);
        file_put_contents('.stylelintrc.yaml', self::$stylelintrc);
    }

    public function testDefault() {
        $rules = ['html' => ['error']];
        $report = processSource($this->source, $rules, 'default.php');

        $this->assertLineColumn([
            [5, 12, 'html/style/color-named'],
            [14, 7, 'html/line-end-style'],
            [2, 1, 'html/head-req-title'],
        ], $report);
    }

}
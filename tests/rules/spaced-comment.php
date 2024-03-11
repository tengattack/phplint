<?php

final class SpacedCommentRuleTest extends RuleTestCase {

    public $ruleId = 'spaced-comment';

    public function testCheckComment() {
        $source = <<<EOF
<?php

//TEMP: xx error
// TEMP：xxx error
// TODO：xxx error
// FIXME：xxx error
// WORKAROUND：xxx error
// NOTICE：xxx error
// REVIEW：xxx error
// TEMP: xxx correct
// TODO: xxx correct
// FIXME: xxx correct
// WORKAROUND: xxx correct
// NOTICE: xxx correct
// REVIEW: xxx correct
EOF;
        $rules = ['spaced-comment' => ['error', 'always', ['exceptions' => '-+*']]];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[3, 1], [4, 1], [5, 1], [6, 1], [7, 1], [8, 1], [9, 1]], $report);
    }
}

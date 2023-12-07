<?php

final class SpacedCommentRuleTest extends RuleTestCase {

    public $ruleId = 'spaced-comment';

    public function testCheckComment() {
        $source = <<<EOF
<?php

// TEMP：xxx
// TODO：xxx
// FIXME：xxx
// WORKAROUND：xxx
// NOTICE：xxx
// REVIEW：xxx
// TEMP: xxx
// TODO: xxx
// FIXME: xxx
// WORKAROUND: xxx
// NOTICE: xxx
// REVIEW: xxx
EOF;
        $rules = ['spaced-comment' => ['error', 'always', ['exceptions' => '-+*']]];
        $report = processSource($source, $rules);
        $this->assertLineColumn([[3, 1], [4, 1], [5, 1], [6, 1], [7, 1], [8, 1]], $report);
    }
}

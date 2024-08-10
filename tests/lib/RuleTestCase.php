<?php

use PHPUnit\Framework\TestCase;

// RuleTestCase with custom assertions
// see more: https://phpunit.readthedocs.io/en/7.3/extending-phpunit.html#subclass-phpunit-framework-testcase
abstract class RuleTestCase extends TestCase {
  /**
   * @var string ruleId
   */
  public $ruleId;

  public function assertLineColumn($linecolumns, $report) {
    $messages = $report->getResult()->messages;

    $this->assertCount(count($linecolumns), $messages);
    for ($i = 0; $i < count($linecolumns); $i++) {
      $pos = $linecolumns[$i];
      $expectedSubset = [
        'ruleId' => count($pos) > 2 ? $pos[2] : $this->ruleId,
        'line' => $pos[0],  // line
        'column' => $pos[1],  // column
      ];
      foreach ($expectedSubset as $key => $value) {
        $this->assertArrayHasKey($key, $messages[$i]);
        $this->assertSame($value, $messages[$i][$key]);
      }
    }
  }

  public function assertContainsMessage($message, $report) {
    $messages = $report->getResult()->messages;
    $found = false;
    foreach ($messages as $m) {
      if ($m['message'] === $message) {
        $found = true;
        break;
      }
    }
    $this->assertTrue($found, "It should contain message '" . $message . "'");
  }
}

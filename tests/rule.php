<?php

use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase {

  public function testRemoveComments() {
    $source = <<<EOF
<?php
/* foo */
echo \$a;  // bar
# sharp
EOF;
    $result = Rule::removeComments($source);
    $this->assertStringNotContainsString('foo', $result);
    $this->assertStringNotContainsString('bar', $result);
    $this->assertStringNotContainsString('sharp', $result);
    $this->assertStringContainsString('echo $a', $result);
  }
}

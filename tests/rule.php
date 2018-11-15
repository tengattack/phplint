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
    $this->assertNotContains('foo', $result);
    $this->assertNotContains('bar', $result);
    $this->assertNotContains('sharp', $result);
    $this->assertContains('echo $a', $result);
  }
}

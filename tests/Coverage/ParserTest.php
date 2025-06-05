<?php

namespace Orbeji\PrCoverageChecker\Coverage;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testGetCoverageLines(): void
    {
        $parser = new Parser();
        $coverage = '<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1709502639">
  <project timestamp="1709502639">
    <file name="/app/src/Dummy.php">
      <class name="Orbeji\Test\Dummy" namespace="global">
        <metrics complexity="5" methods="2" coveredmethods="1" conditionals="0" coveredconditionals="0" statements="7" coveredstatements="6" elements="9" coveredelements="7"/>
      </class>
      <line num="9" type="method" name="fibonacci" visibility="public" complexity="2" crap="2" count="1"/>
      <line num="10" type="stmt" count="1"/>
      <line num="11" type="stmt" count="1"/>
      <line num="13" type="stmt" count="1"/>
      <line num="17" type="method" name="calculateHypotenuse" visibility="public" complexity="3" crap="3.14" count="1"/>
      <line num="19" type="stmt" count="0"/>
      <line num="20" type="stmt" count="0"/>
      <line num="24" type="stmt" count="1"/>
      <line num="26" type="stmt" count="0"/>
      <metrics loc="28" ncloc="26" classes="1" methods="2" coveredmethods="1" conditionals="0" coveredconditionals="0" statements="7" coveredstatements="6" elements="9" coveredelements="7"/>
    </file>
    <metrics files="1" loc="28" ncloc="26" classes="1" methods="2" coveredmethods="1" conditionals="0" coveredconditionals="0" statements="7" coveredstatements="6" elements="9" coveredelements="7"/>
  </project>
</coverage>';
        [$uncoveredLines, $coveredLines] = $parser->getCoverageLines($coverage);
        $expectedUncovered = [
            'src/Dummy.php' => [
                19,
                20,
                26,
            ],
        ];
        $exprectedCovered = [
            'src/Dummy.php' => [
                9,
                10,
                11,
                13,
                17,
                24,
            ],
        ];
        $this->assertEquals($expectedUncovered, $uncoveredLines);
        $this->assertEquals($exprectedCovered, $coveredLines);
    }

    public function testGetModifiedLinesFromDiff(): void
    {
        $parser = new Parser();
        $diff = 'diff --git a/src/Dummy.php b/src/Dummy.php
index 629c557..e34e94f 100644
--- a/src/Dummy.php
+++ b/src/Dummy.php
@@ -2,6 +2,8 @@
 
 namespace Orbeji\\Test;
 
+use InvalidArgumentException;
+
 class Dummy
 {
     public function fibonacci(int $n) {
@@ -11,4 +13,16 @@ class Dummy
             return $this->fibonacci($n - 1) + $this->fibonacci($n - 2);
         }
     }
+
+    public function calculateHypotenuse($a, $b) {
+        // Ensure the input values are non-negative
+        if ($a < 0 || $b < 0) {
+            throw new InvalidArgumentException("Side lengths must be non-negative.");
+        }
+
+        // Calculate the length of the hypotenuse
+        $c = sqrt($a**2 + $b**2);
+
+        return $c;
+    }
 }
\\ No newline at end of file
';
        $modifiedLines = $parser->getPrModifiedLines($diff);
        $expected = [
            'src/Dummy.php' => [5, 6, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27,],
        ];

        $this->assertEquals($expected, $modifiedLines);
    }

    public function testFilter(): void
    {
        $parser = new Parser();
        $modifiedLines = [
            'src/Dummy.php' => [5, 6, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27,],
        ];
        $uncovered = [
            'src/Dummy.php' => [19, 20, 26,],
        ];
        $covered = [
            'src/Dummy.php' => [9, 10, 11, 13, 17, 24,],
        ];
        $filtered = $parser->filterModifiedLinesNotInReport($modifiedLines, $uncovered, $covered);
        $expected = [
            'src/Dummy.php' => [19, 20, 26, 17, 24,],
        ];
        $this->assertEquals($expected, $filtered);
    }

    public function testGetModifiedLinesUncovered(): void
    {
        $parser = new Parser();
        $modifiedLines = [
            'src/Dummy.php' => [5, 6, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27,],
        ];
        $uncovered = [
            'src/Dummy.php' => [19, 20, 26,],
        ];
        $result = $parser->getModifiedLinesUncovered($modifiedLines, $uncovered);
        $expected = [
            'src/Dummy.php' => [19, 20, 26,],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testCalculatePercentage(): void
    {
        $parser = new Parser();
        $uncovered = [
            'src/Dummy.php' => [19, 20, 21, 22, 23, 24, 25,],
        ];
        $modifiedLines = [
            'src/Dummy.php' => [5, 6, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27,],
        ];
        $percentage = $parser->calculateCoveragePercentage($uncovered, $modifiedLines);
        $this->assertEquals(50.0, $percentage);
    }

    public function testCalculatePercentageNoModifiedLines(): void
    {
        $parser = new Parser();
        $uncovered = [
            'src/Dummy.php' => [19, 20, 21, 22, 23, 24, 25,],
        ];
        $modifiedLines = [
            'src/Dummy.php' => [],
        ];
        $percentage = $parser->calculateCoveragePercentage($uncovered, $modifiedLines);
        $this->assertEquals(100.0, $percentage);
    }
}

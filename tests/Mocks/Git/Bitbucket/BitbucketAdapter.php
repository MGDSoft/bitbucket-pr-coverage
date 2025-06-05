<?php

namespace Orbeji\PrCoverageChecker\Mocks\Git\Bitbucket;

use Orbeji\PrCoverageChecker\Git\GitAPIAdapterInterface;

class BitbucketAdapter implements GitAPIAdapterInterface
{
    public function __construct(string $workspace, string $repo, string $bearerToken)
    {
    }

    public function getPullRequestDiff(int $pullRequestId): string
    {
        return 'diff --git a/src/Dummy.php b/src/Dummy.php
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
    }

    public function createCoverageComment(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void {
    }

    public function addCoverageComment(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        string $pullRequestId,
        string $commitId
    ): void {
    }

    public function createCoverageReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void {
    }
}
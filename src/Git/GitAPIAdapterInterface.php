<?php

namespace Orbeji\PrCoverageChecker\Git;

use Orbeji\PrCoverageChecker\Exception\GitApiException;

interface GitAPIAdapterInterface
{
    /**
     * @throws GitApiException
     */
    public function getPullRequestDiff(int $pullRequestId): string;

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @throws GitApiException
     */
    public function createCoverageComment(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void;

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @throws GitApiException
     */
    public function createCoverageReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void;
}

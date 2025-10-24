<?php

namespace BitBucketPRCoverage\Coverage;

class CalcCoverageOutDto
{
    public function __construct(
        public int   $coveragePercentage,
        public array $modifiedLinesUncovered,
        public float $coverageTotal,
        public float $avgComplexityTotal,
    )
    {
    }
}
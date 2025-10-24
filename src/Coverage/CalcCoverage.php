<?php

namespace BitBucketPRCoverage\Coverage;

class CalcCoverage
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function __invoke(string $coverageReport,string $pullRequestDiff): CalcCoverageOutDto
    {
        [$uncoveredLines, $coveredLines] = $this->parser->getCoverageLines($coverageReport);

        $modifiedLines = $this->parser->getPrModifiedLines($pullRequestDiff);
        $modifiedLines = $this->parser->filterModifiedLinesNotInReport($modifiedLines, $uncoveredLines, $coveredLines);

        $modifiedLinesUncovered = $this->parser->getModifiedLinesUncovered($modifiedLines, $uncoveredLines);

        $coveragePercentage = $this->parser->calculateCoveragePercentage($modifiedLinesUncovered, $modifiedLines);

        return new CalcCoverageOutDto(
            $coveragePercentage,
            $modifiedLinesUncovered,
            $this->parser->getCoverageTotal($coverageReport),
            $this->parser->avgComplexityTotal($coverageReport),
        );
    }
}

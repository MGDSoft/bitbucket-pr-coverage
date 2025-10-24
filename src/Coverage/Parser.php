<?php

namespace BitBucketPRCoverage\Coverage;

use Exception;
use SimpleXMLElement;

class Parser
{
    /**
     * @return array<string,array<int>>
     */
    public function getPrModifiedLines(string $diff): array
    {
        $parser = new \ptlis\DiffParser\Parser();
        $changeSet = $parser->parse($diff);
        $modifiedLines = [];
        foreach ($changeSet->getFiles() as $file) {
            if (str_ends_with($file->getNewFilename(), '.php')) {
                foreach ($file->getHunks() as $hunk) {
                    foreach ($hunk->getLines() as $line) {
                        if ($line->getOperation() !== 'unchanged') {
                            $modifiedLines[$file->getNewFilename()][] = $line->getNewLineNo();
                        }
                    }
                }
            }
        }
        return $modifiedLines;
    }

    /**
     * @return array<int, array<string, array<int, int>>>
     * @throws Exception
     */
    public function getCoverageLines(string $coverageReport): array
    {
        $coverage = new SimpleXMLElement($coverageReport);
        $projectXMLElement = $coverage->project;
        $hasPackage = $projectXMLElement->package;
        if ($hasPackage) {
            [$uncoveredLines, $coveredLines] = $this->getPackageLines($projectXMLElement);
        } else {
            [$uncoveredLines, $coveredLines] = $this->getFileLines($projectXMLElement->file);
        }
        return [$uncoveredLines, $coveredLines];
    }

    public function getCoverageTotal(string $coverageReport): float
    {
        $coverage = new SimpleXMLElement($coverageReport);
        $metrics = $coverage->project->metrics;

        $totalStatements = (int) $metrics['statements'];
        $coveredStatements = (int) $metrics['coveredstatements'];

        return round($coveredStatements / $totalStatements * 100, 2);
    }

    public function avgComplexityTotal(string $coverageReport): float
    {
        $xml = new SimpleXMLElement($coverageReport);

        $classes = $xml->xpath(
            '//class[not(contains(@name, "Test"))]/metrics/@complexity'
        ) ?: [];

        $totalComplexity = array_sum(array_map(fn($n) => (int) $n->complexity, $classes));

        return round(count($classes)  ? ($totalComplexity / count($classes)) * 9 : 0, 2);
    }

    /**
     * @return array<int, array<string, array<int, int>>>
     */
    private function getPackageLines(SimpleXMLElement $project): array
    {
        $uncoveredLines = [];
        $coveredLines = [];
        foreach ($project->package as $package) {
            [$uncoveredLinesResult, $coveredLinesResult] = $this->getFileLines($package->file);
            $uncoveredLines[] = $uncoveredLinesResult;
            $coveredLines[] = $coveredLinesResult;
        }
        $uncoveredLines = array_merge([], ...$uncoveredLines);
        $coveredLines = array_merge([], ...$coveredLines);
        return [$uncoveredLines, $coveredLines];
    }

    /**
     * @return array<int, array<string, array<int, int>>>
     */
    private function getFileLines(SimpleXMLElement $files): array
    {
        $uncoveredLines = [];
        $coveredLines = [];
        foreach ($files as $file) {
            $filename = $this->parseName((string)$file['name']);
            foreach ($file->line as $line) {
                if ((int)$line['count'] === 0) {
                    $uncoveredLines[$filename][] = (int)$line['num'];
                } else {
                    $coveredLines[$filename][] = (int)$line['num'];
                }
            }
        }
        return array($uncoveredLines, $coveredLines);
    }

    private function parseName(string $fileName): string
    {
        $filteredName = strstr($fileName, 'src/');
        return $filteredName !== false ? $filteredName : $fileName;
    }

    /**
     * @param array<string,array<int>> $modifiedFileLines
     * @param array<string,array<int>> $uncoveredLines
     * @return array<string,array<int>>
     */
    public function getModifiedLinesUncovered(array $modifiedFileLines, array $uncoveredLines): array
    {
        $matchedLines = [];
        foreach ($modifiedFileLines as $file => $modifiedLines) {
            if (array_key_exists($file, $uncoveredLines)) {
                foreach ($modifiedLines as $modifiedLine) {
                    if (in_array($modifiedLine, $uncoveredLines[$file], true)) {
                        $matchedLines[$file][] = $modifiedLine;
                    }
                }
            }
        }
        return $matchedLines;
    }

    /**
     * @param array<string,array<int>> $modifiedLines
     * @param array<string,array<int>> $uncoveredLines
     * @param array<string,array<int>> $coveredLines
     * @return array<string,array<int>>
     */
    public function filterModifiedLinesNotInReport(
        array $modifiedLines,
        array $uncoveredLines,
        array $coveredLines
    ): array {
        $newModifiedLines = [];
        foreach ($modifiedLines as $file => $lines) {
            if (array_key_exists($file, $uncoveredLines)) {
                foreach ($lines as $line) {
                    if (in_array($line, $uncoveredLines[$file], true)) {
                        $newModifiedLines[$file][] = $line;
                    }
                }
            }
            if (array_key_exists($file, $coveredLines)) {
                foreach ($lines as $line) {
                    if (in_array($line, $coveredLines[$file], true)) {
                        $newModifiedLines[$file][] = $line;
                    }
                }
            }
        }
        return $newModifiedLines;
    }

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @param array<string,array<int>> $modifiedLines
     * @return float
     */
    public function calculateCoveragePercentage(array $modifiedLinesUncovered, array $modifiedLines): float
    {
        $countModifiedLinesUncovered = $this->countLines($modifiedLinesUncovered);
        $countModifiedLines = $this->countLines($modifiedLines);
        if ($countModifiedLines === 0) {
            return 100.0;
        }
        return round(100 - (($countModifiedLinesUncovered) / ($countModifiedLines)) * 100, 2);
    }

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @return int
     */
    private function countLines(array $modifiedLinesUncovered): int
    {
        $count = 0;
        foreach ($modifiedLinesUncovered as $file) {
            foreach ($file as $ignored) {
                $count++;
            }
        }
        return $count;
    }
}

<?php

namespace BitBucketPRCoverage\Report;

use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReportHelper
{
    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     */
    public static function createMarkdownBitbucketReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        string $commitId,
        string $workspace,
        string $repo
    ): string {
        $report = 'Coverage: **' . $coveragePercentage . '%**' . PHP_EOL . PHP_EOL;
        $report .= '|**File**|**Uncovered lines**|' . PHP_EOL;
        $report .= '|---|---|' . PHP_EOL;

        foreach ($modifiedLinesUncovered as $file => $lines) {
            $href = sprintf(
                'https://bitbucket.org/%s/%s/src/%s/%s#lines-%s',
                $workspace,
                $repo,
                $commitId,
                $file,
                implode(',', $lines)
            );
            $report .= '|[' . $file . '](' . $href . ')|';
            foreach ($lines as $line) {
                $href = sprintf(
                    'https://bitbucket.org/%s/%s/src/%s/%s#lines-%s',
                    $workspace,
                    $repo,
                    $commitId,
                    $file,
                    $line
                );
                $report .= ' [' . $line . '](' . $href . ')';
            }
            $report .= '|' . PHP_EOL;
        }
        return $report;
    }

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     */
    public static function createAnsiReport(
        InputInterface $input,
        OutputInterface $output,
        float $coveragePercentage,
        array $modifiedLinesUncovered
    ): void {
        $output->writeln('Coverage: <error>' . $coveragePercentage . '%</error>');
        $symfonyStyle = new SymfonyStyle($input, $output);
        $rows = [];
        foreach ($modifiedLinesUncovered as $file => $lines) {
            $rows[] = [
                new TableCell($file),
                new TableCell(implode(', ', $lines)),
            ];
        }
        $symfonyStyle->table(
            ['File', 'Uncovered Lines'],
            $rows
        );
    }
}

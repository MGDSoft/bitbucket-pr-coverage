<?php

namespace BitBucketPRCoverage;

use BitBucketPRCoverage\Git\Bitbucket\BitbucketAdapter;
use Exception;
use InvalidArgumentException;
use BitBucketPRCoverage\Coverage\Parser;
use BitBucketPRCoverage\Exception\GitApiException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

class PrCoverageCheckerCommand extends Command
{
    private BitbucketAdapter $gitService;

    public function __construct(private Parser $parser)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('shopadvizor:pull_request:coverage_report')
            ->setDescription('Generate report')
            ->addOption('coverage_report_path',null,InputOption::VALUE_REQUIRED,'Path to coverage report')
            ->addOption('pullrequest_id',null,InputOption::VALUE_REQUIRED,'Identifier of the pull request to be checked')
            ->addOption('workspace',null,InputOption::VALUE_REQUIRED,'Workspace of the repository in Bitbucket or owner in Github')
            ->addOption('repository',null,InputOption::VALUE_REQUIRED,'Repository name')
            ->addOption('api_token',null,InputOption::VALUE_REQUIRED,'Token to obtain the diff of the PR from the API')
            ->addOption('git_branch_diff',null,InputOption::VALUE_OPTIONAL,'Path to diff file', 'origin/develop')
            ->addOption('percentage',null, InputOption::VALUE_OPTIONAL,'Required coverage percentage of the new code in the PullRequest', '80')
        ;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $coverageReportPath = $input->getOption('coverage_report_path');
        $this->gitService = new BitbucketAdapter(
            $input->getOption('workspace'),
            $input->getOption('repository'),
            $input->getOption('api_token'),
            $input->getOption('pullrequest_id'),
        );

        if (!file_exists($coverageReportPath)) {
            throw new InvalidArgumentException('$coverageReportPath Files does not exist: ' . $coverageReportPath);
        }
        $coverageReport = file_get_contents($coverageReportPath);
        if (!$coverageReport) {
            throw new InvalidArgumentException('Cannot read file: ' . $coverageReportPath);
        }

        $process = Process::fromShellCommandline("git diff ".$input->getOption('git_branch_diff')." > /tmp/diff.txt");
        $process->start();
        $output->writeln($process->getOutput());

        $pullRequestDiff = file_get_contents('/tmp/diff.txt');

        [$coveragePercentage, $modifiedLinesUncovered] = $this->check($coverageReport, $pullRequestDiff);

        $this->createReport($coveragePercentage, $modifiedLinesUncovered, $input, $output);

        return Command::SUCCESS;
    }

    /**
     * @param string $coverageReport
     * @param string $pullRequestDiff
     * @return array{0:float, 1:array<string,array<int>>}
     * @throws Exception
     */
    private function check(
        string $coverageReport,
        string $pullRequestDiff
    ): array {
        [$uncoveredLines, $coveredLines] = $this->parser->getCoverageLines($coverageReport);
        $modifiedLines = $this->parser->getPrModifiedLines($pullRequestDiff);
        $modifiedLines = $this->parser->filterModifiedLinesNotInReport($modifiedLines, $uncoveredLines, $coveredLines);

        $modifiedLinesUncovered = $this->parser->getModifiedLinesUncovered($modifiedLines, $uncoveredLines);

        $coveragePercentage = $this->parser->calculateCoveragePercentage($modifiedLinesUncovered, $modifiedLines);

        return [$coveragePercentage, $modifiedLinesUncovered];
    }

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @throws GitApiException
     */
    public function createReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        InputInterface $input,
        OutputInterface $output
    ): void {

        $pullRequestId = (int) $input->getOption('pullrequest_id');
        Assert::integer($pullRequestId);

        ReportHelper::createAnsiReport($input, $output, $coveragePercentage, $modifiedLinesUncovered);
        $this->gitService->createCoverageReport($coveragePercentage, $modifiedLinesUncovered, $pullRequestId);
    }
}

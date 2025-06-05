<?php

namespace Orbeji\PrCoverageChecker;

use Exception;
use InvalidArgumentException;
use Orbeji\PrCoverageChecker\Coverage\Parser;
use Orbeji\PrCoverageChecker\Exception\GitApiException;
use Orbeji\PrCoverageChecker\Git\GitAdapterFactory;
use Orbeji\PrCoverageChecker\Git\GitAPIAdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class PrCoverageChecker extends Command
{
    /**
     * @var GitAPIAdapterInterface
     */
    private $gitService;

    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var GitAdapterFactory
     */
    private $gitAdapterFactory;

    public function __construct(Parser $parser, GitAdapterFactory $gitAdapterFactory)
    {
        parent::__construct();
        $this->parser = $parser;
        $this->gitAdapterFactory = $gitAdapterFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName('check')
            ->setDescription('Checks the coverages of the specified PullRequest')
            ->setHelp('This command allows you to create a user...')
            ->addArgument(
                'coverage_report',
                InputArgument::REQUIRED,
                'Path to coverage report'
            )
            ->addArgument(
                'percentage',
                InputArgument::REQUIRED,
                'Required coverage percentage of the new code in the PullRequest'
            )
            ->addOption(
                'diff',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to diff file'
            )
            ->addOption(
                'pullrequest-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Identifier of the pull request to be checked'
            )
            ->addOption(
                'provider',
                null,
                InputOption::VALUE_REQUIRED,
                'Git provider, available options are Bitbucket or Github'
            )
            ->addOption(
                'workspace',
                null,
                InputOption::VALUE_REQUIRED,
                'Workspace of the repository in Bitbucket or owner in Github'
            )
            ->addOption(
                'repository',
                null,
                InputOption::VALUE_REQUIRED,
                'Repository name'
            )
            ->addOption(
                'api_token',
                null,
                InputOption::VALUE_REQUIRED,
                'Token to obtain the diff of the PR from the API'
            )
            ->addOption(
                'report',
                null,
                InputOption::VALUE_REQUIRED,
                'Available options: -comment: Creates a simple coverage report of the uncovered lines ' .
                'in a comment of the pull request
                -report: (Only for Bitbucket) Creates a report associated to the PullRequest with annotations of ' .
                'the uncovered lines
                -ansi: Shows a report in the console with the uncovered lines for every file'
            );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $coverageReportPath = $input->getArgument('coverage_report');
        Assert::string($coverageReportPath);

        $expectedPercentage = $input->getArgument('percentage');
        if (!ctype_digit($expectedPercentage)) {
            throw new InvalidArgumentException('Expected integer');
        }
        $expectedPercentage = (int)$expectedPercentage;

        $this->checkDiffFileOrAPI($input);

        $isDiffFileFlow = $input->getOption('diff') !== null;

        $provider = $input->getOption('provider');
        Assert::string($provider);

        $workspace = $input->getOption('workspace');
        Assert::string($workspace);

        $repository = $input->getOption('repository');
        Assert::string($repository);

        $apiToken = $input->getOption('api_token');
        Assert::string($apiToken);

        $this->gitService = $this->gitAdapterFactory->create($provider, $workspace, $repository, $apiToken);

        if (!file_exists($coverageReportPath)) {
            throw new InvalidArgumentException('Files does not exist: ' . $coverageReportPath);
        }
        $coverageReport = file_get_contents($coverageReportPath);
        if ($coverageReport === false) {
            throw new InvalidArgumentException('Cannot read file: ' . $coverageReportPath);
        }

        $pullRequestDiff = $this->getPullRequestDiff($input, $isDiffFileFlow);
        [$coveragePercentage, $modifiedLinesUncovered] = $this->check($coverageReport, $pullRequestDiff);

        if ($input->getOption('report')) {
            $output->writeln('pre report');
            $this->createReport($isDiffFileFlow, $coveragePercentage, $modifiedLinesUncovered, $input, $output);
            $output->writeln('report sent');
        } else {
            $output->writeln('Coverage: <error>' . $coveragePercentage . '%</error>');
        }

        return Command::SUCCESS;
    }

    private function checkDiffFileOrAPI(InputInterface $input): void
    {
        if (
            !$input->getOption('diff') &&
            (
                !$input->getOption('provider') ||
                !$input->getOption('workspace') ||
                !$input->getOption('repository') ||
                !$input->getOption('api_token')
            )
        ) {
            throw new InvalidArgumentException(
                'If no diff file defined then you must pass the git configuration arguments ' .
                '[provider, workspace, repository and api_token]'
            );
        }
    }

    /**
     * @throws GitApiException
     */
    public function getPullRequestDiff(InputInterface $input, bool $isDiffFileFlow): string
    {
        if ($isDiffFileFlow) {
            $diffPath = $input->getOption('diff');
            Assert::string($diffPath);
            if (!file_exists($diffPath)) {
                throw new InvalidArgumentException('Files does not exist: ' . $diffPath);
            }
            $diff = file_get_contents($diffPath);
            if ($diff === false) {
                throw new InvalidArgumentException('Cannot read file diff file');
            }
            return $diff;
        }

        $pullRequestId = (int) $input->getOption('pullrequest-id');
        Assert::integer($pullRequestId);
        return $this->gitService->getPullRequestDiff($pullRequestId);
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
        bool $isDiffFileFlow,
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $report = $input->getOption('report');
        Assert::string($report);

        if ($report === 'ansi') {
            ReportHelper::createAnsiReport($input, $output, $coveragePercentage, $modifiedLinesUncovered);
        }

        $pullRequestId = (int) $input->getOption('pullrequest-id');
        Assert::integer($pullRequestId);
        if ($report === 'comment') {
            $this->gitService->createCoverageComment($coveragePercentage, $modifiedLinesUncovered, $pullRequestId);
        }
        if ($report === 'report') {
            $this->gitService->createCoverageReport($coveragePercentage, $modifiedLinesUncovered, $pullRequestId);
        }

    }
}

<?php

namespace BitBucketPRCoverage;

use BitBucketPRCoverage\Bitbucket\BitbucketAdapter;
use BitBucketPRCoverage\Coverage\CalcCoverage;
use BitBucketPRCoverage\Git\GitDiffGenerator;
use BitBucketPRCoverage\Report\ReportHelper;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrCoverageCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('coverage_report')
            ->setDescription('Generate report')
            ->addOption('coverage_report_path',null,InputOption::VALUE_REQUIRED,'Path to coverage report')
            ->addOption('git_branch_diff',null,InputOption::VALUE_OPTIONAL,'Path to diff file', 'origin/develop')
            ->addOption('pullrequest_id',null,InputOption::VALUE_REQUIRED,'Identifier of the pull request to be checked', getenv('BITBUCKET_PR_ID'))
            ->addOption('workspace',null,InputOption::VALUE_REQUIRED,'Workspace of the repository in Bitbucket or owner in Github', getenv('BITBUCKET_WORKSPACE'))
            ->addOption('repository',null,InputOption::VALUE_REQUIRED,'Repository name', getenv('BITBUCKET_REPO_SLUG'))
            ->addOption('api_token',null,InputOption::VALUE_REQUIRED,'Token to obtain the diff of the PR from the API', getenv('BITBUCKET_TOKEN'))
        ;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $coverageReportPath = $input->getOption('coverage_report_path');
        $coverageReport = @file_get_contents($coverageReportPath);

        if (!$coverageReport) {
            throw new InvalidArgumentException('Cannot read file: ' . $coverageReportPath);
        }

        $pullRequestDiff = (new GitDiffGenerator())->__invoke($input->getOption('git_branch_diff'));
        [$coveragePercentage, $modifiedLinesUncovered] = (new CalcCoverage())->__invoke($coverageReport, $pullRequestDiff);

        ReportHelper::createAnsiReport($input, $output, $coveragePercentage, $modifiedLinesUncovered);
        $bitbucketAdapter = new BitbucketAdapter(
            $input->getOption('workspace'),
            $input->getOption('repository'),
            $input->getOption('api_token'),
            $input->getOption('pullrequest_id'),
        );
        $bitbucketAdapter->createCoverageReport($coveragePercentage, $modifiedLinesUncovered);

        return Command::SUCCESS;
    }
}

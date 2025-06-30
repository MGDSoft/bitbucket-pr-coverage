<?php

namespace BitBucketPRCoverage\Git;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class GitDiffGenerator
{
    private string $gitDiffPath = '/tmp/diff.txt';

    public function __invoke(string $gitDiffBranch, OutputInterface $output): string
    {
        @unlink($this->gitDiffPath);

        $command = 'git config --global --add safe.directory '.getenv('BITBUCKET_CLONE_DIR');
        $output->writeln("<info>Running command: $command</info>");
        $process = Process::fromShellCommandline($command);
        $process->run();
        $output->writeln($process->getOutput(). $process->getErrorOutput());

        $command = "git diff $gitDiffBranch > $this->gitDiffPath";
        $output->writeln("<info>Running command: $command</info>");
        $process = Process::fromShellCommandline($command);
        $process->run();
        $output->writeln($process->getOutput(). $process->getErrorOutput());

        if (!file_exists($this->gitDiffPath)) {
            throw new \RuntimeException("Git diff not exist after $command");
        }

        if (!$res = file_get_contents('/tmp/diff.txt')) {
            $output->writeln("<error>Git diff not exist after $command</error>");
        }

        return $res;
    }
}
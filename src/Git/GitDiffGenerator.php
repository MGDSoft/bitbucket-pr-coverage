<?php

namespace BitBucketPRCoverage\Git;

use Symfony\Component\Process\Process;

class GitDiffGenerator
{
    private string $gitDiffPath = '/tmp/diff.txt';

    public function __invoke(string $gitDiffBranch): string
    {
        @unlink($this->gitDiffPath);
        $command = "git diff $gitDiffBranch > $this->gitDiffPath";
        Process::fromShellCommandline($command)->run();

        if (!file_exists($this->gitDiffPath)) {
            throw new \RuntimeException("Git diff not exist after $command");
        }

        return file_get_contents('/tmp/diff.txt');
    }
}
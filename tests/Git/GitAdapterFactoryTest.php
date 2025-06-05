<?php

namespace Orbeji\PrCoverageChecker\Git;

use InvalidArgumentException;
use Orbeji\PrCoverageChecker\Git\Bitbucket\BitbucketAdapter;
use Orbeji\PrCoverageChecker\Git\GitHub\GitHubAdapter;
use PHPUnit\Framework\TestCase;

class GitAdapterFactoryTest extends TestCase
{
    public function testInvalidProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid git provider. Valid options are: Bitbucket or Github');
        $factory = new GitAdapterFactory();
        $factory->create('', '', '', '');
    }

    public function testProviderBitbucket(): void
    {
        $factory = new GitAdapterFactory();
        $gitAdapter = $factory->create(
            'Bitbucket',
            'orbeji',
            'test',
            'token'
        );
        $this->assertInstanceOf(BitbucketAdapter::class, $gitAdapter);
    }

    public function testProviderGithub(): void
    {
        $factory = new GitAdapterFactory();
        $gitAdapter = $factory->create(
            'Github',
            'orbeji',
            'test',
            'token'
        );
        $this->assertInstanceOf(GitHubAdapter::class, $gitAdapter);
    }
}

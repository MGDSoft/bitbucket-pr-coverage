<?php

namespace Orbeji\PrCoverageChecker\Git;

use InvalidArgumentException;
use Orbeji\PrCoverageChecker\Git\Bitbucket\BitbucketAdapter;
use Orbeji\PrCoverageChecker\Git\GitHub\GitHubAdapter;

class GitAdapterFactory
{
    /**
     * @param string $provider
     * @param string $workspace
     * @param string $repository
     * @param string $apiToken
     * @return GitAPIAdapterInterface
     */
    public function create(
        string $provider,
        string $workspace,
        string $repository,
        string $apiToken
    ): GitAPIAdapterInterface {
        switch ($provider) {
            case 'Bitbucket':
                return new BitbucketAdapter($workspace, $repository, $apiToken);
            case 'Github':
                return new GithubAdapter($workspace, $repository, $apiToken);
            default:
                throw new InvalidArgumentException('Invalid git provider. Valid options are: Bitbucket or Github');
        }
    }
}

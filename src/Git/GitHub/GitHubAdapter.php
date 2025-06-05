<?php

namespace Orbeji\PrCoverageChecker\Git\GitHub;

use Orbeji\PrCoverageChecker\Exception\GitApiException;
use Orbeji\PrCoverageChecker\Git\GitAPIAdapterInterface;
use Orbeji\PrCoverageChecker\ReportHelper;
use Unirest\Request;
use Unirest\Response;

class GitHubAdapter implements GitAPIAdapterInterface
{
    /**
     * @var string
     */
    private $owner;
    /**
     * @var string
     */
    private $repository;
    /**
     * @var string
     */
    private $bearerToken;

    public function __construct(string $owner, string $repository, string $bearerToken)
    {
        $this->owner = $owner;
        $this->repository = $repository;
        $this->bearerToken = $bearerToken;
    }

    /**
     * @throws GitApiException
     */
    public function getPullRequestDiff(int $pullRequestId): string
    {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/vnd.github.diff',
            'X-GitHub-Api-Version' => '2022-11-28',
        );

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/pulls/%s',
            $this->owner,
            $this->repository,
            $pullRequestId
        );

        $response = Request::get(
            $url,
            $headers
        );

        if ($response->code !== 200) {
            $message = $this->getErrorMessage($response);
            throw new GitApiException($message);
        }

        return $response->raw_body;
    }

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @throws GitApiException
     */
    public function createCoverageComment(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void {
        $commitId = $this->getPullRequestCommitId($pullRequestId);
        $htmlReport = ReportHelper::createGithubHtmlReport(
            $coveragePercentage,
            $modifiedLinesUncovered,
            $commitId,
            $this->owner,
            $this->repository
        );
        $this->commentHtmlReport($pullRequestId, $htmlReport);
    }

    /**
     * @throws GitApiException
     */
    public function getPullRequestCommitId(int $pullRequestId): string
    {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        );

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/pulls/%s',
            $this->owner,
            $this->repository,
            $pullRequestId
        );

        $response = Request::get(
            $url,
            $headers
        );

        if ($response->code !== 200) {
            $message = $this->getErrorMessage($response);
            throw new GitApiException($message);
        }

        return $response->body->merge_commit_sha;
    }


    /**
     * @throws GitApiException
     */
    private function commentHtmlReport(int $pullRequestId, string $htmlReport): void
    {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        );

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/issues/%s/comments',
            $this->owner,
            $this->repository,
            $pullRequestId
        );

        $body = ['body' => $htmlReport];

        $response = Request::post(
            $url,
            $headers,
            json_encode($body)
        );

        if ($response->code !== 200) {
            $message = $this->getErrorMessage($response);
            throw new GitApiException($message);
        }
    }

    public function createCoverageReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void {
        // For now only in Bitbucket
    }

    private function getErrorMessage(Response $response): string
    {
        $message = 'API error';
        if (json_validate($response->raw_body)) {
            $error = json_decode($response->raw_body, true);
            if (is_array($error) && array_key_exists('message', $error)) {
                $message = $error['message'] ?? '';
            }
        }
        return '(' . $response->code . ') ' . $message;
    }
}

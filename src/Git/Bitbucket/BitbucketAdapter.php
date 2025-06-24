<?php

namespace BitBucketPRCoverage\Git\Bitbucket;

use BitBucketPRCoverage\Exception\GitApiException;
use BitBucketPRCoverage\ReportHelper;
use stdClass;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BitbucketAdapter
{
    private HttpClientInterface $client;

    public function __construct(string $workspace, string $repository, string $bearerToken, private string $pullRequestId)
    {
        $this->client = HttpClient::createForBaseUri(
            "https://api.bitbucket.org/2.0/repositories/$workspace/$repository/",
            ['headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
                'Content-Type' => 'application/json',
            ]],
        );
    }

    /**
     * @throws GitApiException
     */
    private function getCommitIdFromPullRequest(): string
    {
        $response = $this->client->request('GET', "pullrequests/$this->pullRequestId/");

        return $response->toArray()['source']['commit']['hash'];
    }


    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @throws GitApiException
     */
    public function createCoverageReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
        int $pullRequestId
    ): void {
        $commitId = $this->getCommitIdFromPullRequest($pullRequestId);
        $this->deleteOutdatedCoverageReports($commitId);
        $idReport = $this->createReport($coveragePercentage, $commitId);
        $this->addAnnotations($idReport, $modifiedLinesUncovered, $commitId);
    }

    /**
     * @throws GitApiException
     */
    private function deleteOutdatedCoverageReports(string $commitId): void
    {
        $coverageReports = $this->getCoverageReports($commitId);
        foreach ($coverageReports as $coverageReport) {
            $this->deleteReport($commitId, $coverageReport);
        }
    }

    /**
     * @return array<stdClass>
     * @throws GitApiException
     */
    private function getCoverageReports(string $commitId): array
    {
        $reports = $this->listReports($commitId);
        $coverageReports = [];
        foreach ($reports as $report) {
            if ($report->report_type === 'COVERAGE') {
                $coverageReports[] = $report;
            }
        }
        return $coverageReports;
    }

    private function listReports(string $commitId): array
    {
        $response = $this->client->request('GET', "commit/$commitId/reports");
        return $response->toArray()['values'];
    }

    /**
     * @throws GitApiException
     */
    private function deleteReport(string $commitId, stdClass $coverageReport): void
    {
        $this->client->request('DELETE', "commit/$commitId/reports/".$coverageReport->external_id);
    }

    private function createReport(float $coveragePercentage, string $commitId): string
    {
        $idReport = uniqid();

        $body = [
            "external_id" => $idReport,
            "title" => "Coverage report",
            "details" => "Coverage report of the modified/created code",
            "report_type" => "COVERAGE",
            "result" => $coveragePercentage <= 80 ? "FAILED" : "PASSED",
            "data" => [
                [
                    "type" => "PERCENTAGE",
                    "title" => "Coverage of new code",
                    "value" => $coveragePercentage,
                ]
            ]
        ];

        $response = $this->client->request('PUT', "commit/$commitId/reports/$idReport", [
            'json' => $body,
        ]);

        return $response->toArray()['uuid'];
    }

    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     * @throws GitApiException
     */
    private function addAnnotations(?string $idReport, array $modifiedLinesUncovered, string $commitId): void
    {
        if (!$modifiedLinesUncovered) {
            return;
        }

        $body = [];
        foreach ($modifiedLinesUncovered as $file => $lines) {
            foreach ($lines as $line) {
                $body[] = [
                    "external_id" => uniqid(),
                    "annotation_type" => "VULNERABILITY",
                    "summary" => "Line not covered in tests",
                    "severity" => "HIGH",
                    "path" => $file,
                    "line" => $line
                ];
            }
        }

        $this->client->request('POST', "commit/$commitId/reports/$idReport/annotations", [
            'json' => $body,
        ]);
    }
}

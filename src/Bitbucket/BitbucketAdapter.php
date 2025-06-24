<?php

namespace BitBucketPRCoverage\Bitbucket;

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

    private function getCommitIdFromPullRequest(): string
    {
        $response = $this->client->request('GET', "pullrequests/$this->pullRequestId/");

        return $response->toArray()['source']['commit']['hash'];
    }


    /**
     * @param array<string,array<int>> $modifiedLinesUncovered
     */
    public function createCoverageReport(
        float $coveragePercentage,
        array $modifiedLinesUncovered,
    ): void {
        $commitId = $this->getCommitIdFromPullRequest();
        $this->deleteOutdatedCoverageReports($commitId);
        $idReport = $this->createReport($coveragePercentage, $commitId);
        $this->addAnnotations($idReport, $modifiedLinesUncovered, $commitId);
    }

    private function deleteOutdatedCoverageReports(string $commitId): void
    {
        $coverageReports = $this->getCoverageReports($commitId);
        foreach ($coverageReports as $coverageReport) {
            $this->deleteReport($commitId, $coverageReport);
        }
    }

    /**
     * @return array
     */
    private function getCoverageReports(string $commitId): array
    {
        $reports = $this->listReports($commitId);
        $coverageReports = [];
        foreach ($reports as $report) {
            if ($report['report_type'] === 'COVERAGE') {
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

    private function deleteReport(string $commitId, array $coverageReport): void
    {
        $this->client->request('DELETE', "commit/$commitId/reports/".$coverageReport['external_id']);
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
                    "value" => round($coveragePercentage, 2),
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

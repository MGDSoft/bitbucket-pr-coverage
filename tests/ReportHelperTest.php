<?php

namespace Orbeji\PrCoverageChecker;

use PHPUnit\Framework\TestCase;

class ReportHelperTest extends TestCase
{
    public function testCreateGithubHtmlReportNoFiles(): void
    {
        $modifiedLinesUncovered = [];
        $report = ReportHelper::createGithubHtmlReport(
            100,
            $modifiedLinesUncovered,
            'commit_id',
            'owner',
            'repo'
        );
        $this->assertEquals(
            '<div>Coverage: <strong>100%</strong></div>' .
            '<table><thead><th>File</th><th>Uncovered lines</th></thead><tbody></tbody></table>',
            $report
        );
    }

    public function testCreateGithubHtmlReportUnCoveredOneFile(): void
    {
        $modifiedLinesUncovered = [
            'file1' => [1, 2, 3]
        ];
        $report = ReportHelper::createGithubHtmlReport(
            100,
            $modifiedLinesUncovered,
            'commit_id',
            'owner',
            'repo'
        );
        $this->assertEquals(
            '<div>Coverage: <strong>100%</strong></div>' .
            '<table><thead><th>File</th><th>Uncovered lines</th></thead>' .
            '<tbody>' .
            '<tr>' .
            '<td>file1</td>' .
            '<td>' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file1#L1">1</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file1#L2">2</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file1#L3">3</a> ' .
            '</td>' .
            '</tr>' .
            '</tbody>' .
            '</table>',
            $report
        );
    }

    public function testCreateGithubHtmlReportUnCoveredMultipleFiles(): void
    {
        $modifiedLinesUncovered = [
            'file1' => [1, 2, 3],
            'file2' => [1, 2, 3],
            'src/test/file3' => [4, 24, 35],
        ];
        $report = ReportHelper::createGithubHtmlReport(
            100,
            $modifiedLinesUncovered,
            'commit_id',
            'owner',
            'repo'
        );

        $this->assertEquals(
            '<div>Coverage: <strong>100%</strong></div>' .
            '<table>' .
            '<thead><th>File</th><th>Uncovered lines</th></thead>' .
            '<tbody>' .
            '<tr>' .
            '<td>file1</td>' .
            '<td><a href="https://github.com/owner/repo/blob/commit_id/file1#L1">1</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file1#L2">2</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file1#L3">3</a> ' .
            '</td>' .
            '</tr>' .
            '<tr>' .
            '<td>file2</td>' .
            '<td>' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file2#L1">1</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file2#L2">2</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/file2#L3">3</a> ' .
            '</td>' .
            '</tr>' .
            '<tr>' .
            '<td>src/test/file3</td>' .
            '<td>' .
            '<a href="https://github.com/owner/repo/blob/commit_id/src/test/file3#L4">4</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/src/test/file3#L24">24</a> ' .
            '<a href="https://github.com/owner/repo/blob/commit_id/src/test/file3#L35">35</a> ' .
            '</td>' .
            '</tr>' .
            '</tbody>' .
            '</table>',
            $report
        );
    }

    public function testCreateMarkdownBitbucketReportNoFiles(): void
    {
        $modifiedLinesUncovered = [];
        $report = ReportHelper::createMarkdownBitbucketReport(
            100,
            $modifiedLinesUncovered,
            'commit_id',
            'owner',
            'repo'
        );
        $this->assertEquals(
            "Coverage: **100%**

|**File**|**Uncovered lines**|
|---|---|
",
            $report
        );
    }


    public function testCreateMarkdownReportUnCoveredOneFile(): void
    {
        $modifiedLinesUncovered = [
            'file1' => [1, 2, 3]
        ];
        $report = ReportHelper::createMarkdownBitbucketReport(
            100,
            $modifiedLinesUncovered,
            'commit_id',
            'owner',
            'repo'
        );
        $this->assertEquals(
            "Coverage: **100%**

|**File**|**Uncovered lines**|
|---|---|
|[file1](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-1,2,3)| " .
            "[1](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-1) " .
            "[2](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-2) " .
            "[3](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-3)|
",
            $report
        );
    }


    public function testCreateMarkdownReportUnCoveredMultipleFiles(): void
    {
        $modifiedLinesUncovered = [
            'file1' => [1, 2, 3],
            'file2' => [1, 2, 3],
            'src/test/file3' => [4, 24, 35],
        ];
        $report = ReportHelper::createMarkdownBitbucketReport(
            100,
            $modifiedLinesUncovered,
            'commit_id',
            'owner',
            'repo'
        );
        $this->assertEquals(
            "Coverage: **100%**

|**File**|**Uncovered lines**|
|---|---|
|[file1](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-1,2,3)| " .
            "[1](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-1) " .
            "[2](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-2) " .
            "[3](https://bitbucket.org/owner/repo/src/commit_id/file1#lines-3)|
|[file2](https://bitbucket.org/owner/repo/src/commit_id/file2#lines-1,2,3)| " .
            "[1](https://bitbucket.org/owner/repo/src/commit_id/file2#lines-1) " .
            "[2](https://bitbucket.org/owner/repo/src/commit_id/file2#lines-2) " .
            "[3](https://bitbucket.org/owner/repo/src/commit_id/file2#lines-3)|
|[src/test/file3](https://bitbucket.org/owner/repo/src/commit_id/src/test/file3#lines-4,24,35)| " .
            "[4](https://bitbucket.org/owner/repo/src/commit_id/src/test/file3#lines-4) " .
            "[24](https://bitbucket.org/owner/repo/src/commit_id/src/test/file3#lines-24) " .
            "[35](https://bitbucket.org/owner/repo/src/commit_id/src/test/file3#lines-35)|
",
            $report
        );
    }
}

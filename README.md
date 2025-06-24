# Bitbucket PR Coverage

A tool to analyze PHPUnit code coverage for modified lines in Bitbucket pull requests.

## Description

This tool analyzes PHPUnit code coverage reports and git diffs to calculate the test coverage percentage of modified lines in a pull request. It then creates a coverage report in Bitbucket, showing the overall coverage percentage and highlighting uncovered lines.

Key features:
- Focuses only on modified/new lines in the pull request
- Creates a coverage report directly in Bitbucket
- Adds annotations for uncovered lines
- Marks the report as PASSED if coverage is > 80%, otherwise FAILED

## Installation

```bash
composer require mgdsoftware/bitbucket-pr-coverage
```

## Usage

### Basic Usage

```bash
php bin/pr-coverage coverage_report \
  --coverage_report_path=/path/to/clover.xml \
  --api_token=your-bitbucket-token
```

### Using Environment Variables

You can also set the following environment variables instead of passing command-line options:

- `BITBUCKET_PR_ID`: Pull request ID (default in Bitbucket pipelines)
- `BITBUCKET_WORKSPACE`: Bitbucket workspace/owner (default in Bitbucket pipelines)
- `BITBUCKET_REPO_SLUG`: Repository name (default in Bitbucket pipelines)

```bash
# generate clover.xml with PHPUnit
php -d memory_limit=-1 bin/phpunit --log-junit ./test-reports/phpunit.junit.xml --coverage-clover ./test-reports/phpunit.coverage.xml

export BITBUCKET_PR_ID=123
export BITBUCKET_WORKSPACE=your-workspace
export BITBUCKET_REPO_SLUG=your-repo
export BITBUCKET_TOKEN=your-bitbucket-token

php bin/pr-coverage coverage_report --coverage_report_path=$BITBUCKET_CLONE_DIR/test-reports/phpunit.coverage.xml
```

## How It Works

1. Reads the PHPUnit coverage report (XML format)
2. Generates a git diff between the current branch and the target branch
3. Identifies which lines were modified in the pull request
4. Calculates the coverage percentage for the modified lines
5. Creates a coverage report in Bitbucket
6. Adds annotations for uncovered lines

## Requirements

- PHP 8.0 or higher
- PHPUnit XML coverage report
- Bitbucket API token with appropriate permissions

## License

MIT License

## Credits

This project is a fork of [phpunit-pr-coverage-check](https://github.com/orbeji/phpunit-pr-coverage-check) with updated dependencies and code improvements.
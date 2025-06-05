<?php

namespace Orbeji\PrCoverageChecker;

use InvalidArgumentException;
use Nyholm\BundleTest\TestKernel;
use Orbeji\PrCoverageChecker\Coverage\Parser;
use Orbeji\PrCoverageChecker\Git\GitAdapterFactory;
use Orbeji\PrCoverageChecker\Mocks\Git\Bitbucket\BitbucketAdapter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Prevent setting the class alias for all test suites
 */
class PrCoverageCheckerTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);
        return $kernel;
    }

    public function testCommandDiffFile(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                new GitAdapterFactory()
            )
        );

        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $status = $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/clover.xml',
                'percentage' => '90',
                '--diff' => __DIR__ . '/diff.txt',
                '--report' => 'ansi',
            ]
        );

        $this->assertEquals(Command::FAILURE, $status);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals("Coverage: 40%
 --------------- ----------------- 
  File            Uncovered Lines  
 --------------- ----------------- 
  src/Dummy.php   19, 20, 26       
 --------------- ----------------- 

", $output);
    }

    public function testCommandDiffFileDummy(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                new GitAdapterFactory()
            )
        );

        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $status = $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/files/clover.xml',
                'percentage' => '90',
                '--diff' => __DIR__ . '/files/diff.txt',
                '--report' => 'ansi',
            ]
        );

        $this->assertEquals(Command::FAILURE, $status);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals("Coverage: 0%
 --------------- ----------------- 
  File            Uncovered Lines  
 --------------- ----------------- 
  src/Dummy.php   11, 12, 13       
 --------------- ----------------- 

", $output);
    }

    public function testCommand(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $gitFactory = $this->createMock(GitAdapterFactory::class);
        $gitFactory->method('create')->willReturn(new BitbucketAdapter('', '', ''));

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                $gitFactory
            )
        );

        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $status = $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/clover.xml',
                'percentage' => '90',
                '--pullrequest-id' => 1,
                '--provider' => 'Bitbucket',
                '--workspace' => 'orbeji',
                '--repository' => 'test',
                '--api_token' => 'fsfowif',
                '--report' => 'report',
            ]
        );

        $this->assertEquals(Command::FAILURE, $status);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals("", $output);
    }

    public function testCommandComment(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $gitFactory = $this->createMock(GitAdapterFactory::class);
        $gitFactory->method('create')->willReturn(new BitbucketAdapter('', '', ''));

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                $gitFactory
            )
        );

        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $status = $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/clover.xml',
                'percentage' => '90',
                '--pullrequest-id' => 1,
                '--provider' => 'Bitbucket',
                '--workspace' => 'orbeji',
                '--repository' => 'test',
                '--api_token' => 'fsfowif',
                '--report' => 'comment',
            ]
        );

        $this->assertEquals(Command::FAILURE, $status);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals("", $output);
    }

    public function testCommandNoClover(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $gitFactory = $this->createMock(GitAdapterFactory::class);
        $gitFactory->method('create')->willReturn(new BitbucketAdapter('', '', ''));

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                $gitFactory
            )
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Files does not exist: */');
        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/clover_invalid.xml',
                'percentage' => '90',
                '--pullrequest-id' => 1,
                '--provider' => 'Bitbucket',
                '--workspace' => 'orbeji',
                '--repository' => 'test',
                '--api_token' => 'fsfowif',
                '--report' => 'comment',
            ]
        );
    }

    public function testcommandSuccess(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $gitFactory = $this->createMock(GitAdapterFactory::class);
        $gitFactory->method('create')->willReturn(new BitbucketAdapter('', '', ''));

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                $gitFactory
            )
        );

        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $status = $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/clover.xml',
                'percentage' => '40',
                '--pullrequest-id' => 1,
                '--provider' => 'Bitbucket',
                '--workspace' => 'orbeji',
                '--repository' => 'test',
                '--api_token' => 'fsfowif',
                '--report' => 'comment',
            ]
        );

        $this->assertEquals(Command::SUCCESS, $status);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals('', $output);
    }

    public function testcommandMissing(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $gitFactory = $this->createMock(GitAdapterFactory::class);
        $gitFactory->method('create')->willReturn(new BitbucketAdapter('', '', ''));

        $application->add(
            new PrCoverageChecker(
                new Parser(),
                $gitFactory
            )
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('If no diff file defined then you must pass the git configuration arguments [provider, workspace, repository and api_token]');
        $command = $application->find('check');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'coverage_report' => __DIR__ . '/clover.xml',
                'percentage' => '40',
                '--report' => 'comment',
            ]
        );
    }
}

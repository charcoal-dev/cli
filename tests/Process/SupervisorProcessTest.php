<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Tests\Process;

use Charcoal\Cli\Process\Exceptions\ChildProcessCompletedException;
use Charcoal\Cli\Process\Exceptions\ChildSpawnException;
use Charcoal\Cli\Process\Exceptions\UnrecoverableException;
use Charcoal\Cli\Tests\Fixtures\SupervisorProcessStub;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SupervisorProcessTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . "/Fixtures/SupervisorProcessStub.php";
    }

    public function testMasterRecordsChildWithoutExecutingChildLogic(): void
    {
        $process = new SupervisorProcessStub();
        $executed = false;

        $pid = $process->spawnChildProcess(function () use (&$executed): void {
            $executed = true;
        }, []);

        self::assertSame(12345, $pid);
        self::assertSame([12345 => true], $process->supervisorChildren);
        self::assertSame(1, SupervisorProcessStub::$forkCalls);
        self::assertFalse($process->prepared);
        self::assertFalse($executed);
    }

    public function testChildIsPreparedBeforeExecutingLogicWithArguments(): void
    {
        SupervisorProcessStub::$forkResult = 0;
        $process = new SupervisorProcessStub();

        try {
            $process->spawnChildProcess(function (int $value) use ($process): int {
                self::assertTrue($process->prepared);
                self::assertNull($process->supervisorChildren);
                return $value * 2;
            }, [21]);
            self::fail("Child execution must end with a completion exception");
        } catch (ChildProcessCompletedException $exception) {
            self::assertSame(42, $exception->result);
            self::assertSame(1, SupervisorProcessStub::$forkCalls);
        }
    }

    public function testChildCannotSpawnAnotherChild(): void
    {
        SupervisorProcessStub::$forkResult = 0;
        $process = new SupervisorProcessStub();

        try {
            $process->spawnChildProcess(function () use ($process): void {
                SupervisorProcessStub::$forkResult = 12346;
                $process->spawnChildProcess(static fn() => null, []);
            }, []);
            self::fail("A child must not be allowed to spawn another child");
        } catch (UnrecoverableException $exception) {
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
            self::assertSame("This is not a master process; Cannot spawn child",
                $exception->getPrevious()->getMessage());
            self::assertSame(1, SupervisorProcessStub::$forkCalls);
        }
    }

    public function testChildLimitRejectsSpawnBeforeForking(): void
    {
        $process = new SupervisorProcessStub();
        $process->spawnChildProcess(static fn() => null, []);

        try {
            $process->spawnChildProcess(static fn() => null, []);
            self::fail("The child limit must prevent another fork");
        } catch (ChildSpawnException $exception) {
            self::assertSame("Maximum number of child-processes reached (1)", $exception->getMessage());
            self::assertSame([12345 => true], $process->supervisorChildren);
            self::assertSame(1, SupervisorProcessStub::$forkCalls);
        }
    }

    public function testFailedForkDoesNotRegisterAChild(): void
    {
        SupervisorProcessStub::$forkResult = -1;
        $process = new SupervisorProcessStub();

        try {
            $process->spawnChildProcess(static fn() => null, []);
            self::fail("A failed fork must raise a spawn exception");
        } catch (ChildSpawnException $exception) {
            self::assertSame("Failed to spawn a child-process", $exception->getMessage());
            self::assertSame([], $process->supervisorChildren);
            self::assertSame(1, SupervisorProcessStub::$forkCalls);
            self::assertFalse($process->prepared);
        }
    }

    public function testPcntlIsRequiredBeforeForking(): void
    {
        SupervisorProcessStub::$pcntlAvailable = false;
        $process = new SupervisorProcessStub();

        try {
            $process->spawnChildProcess(static fn() => null, []);
            self::fail("Spawning must require the PCNTL extension");
        } catch (\RuntimeException $exception) {
            self::assertSame("Charcoal supervisor process requires PCNTL extension", $exception->getMessage());
            self::assertSame([], $process->supervisorChildren);
            self::assertSame(0, SupervisorProcessStub::$forkCalls);
        }
    }
}

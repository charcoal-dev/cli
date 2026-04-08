<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Tests\Prototype;

use Charcoal\Cli\Contracts\CrashRecoverableProcessInterface;
use Charcoal\Cli\Contracts\Ipc\IpcServerInterface;
use Charcoal\Cli\Contracts\Supervisor\SupervisorInterface;
use Charcoal\Cli\Ipc\MessageFrame;
use Charcoal\Cli\Ipc\Traits\IpcServerTrait;
use Charcoal\Cli\Process\AbstractCliProcess;
use Charcoal\Cli\Process\ProcessRecoveryPolicy;
use Charcoal\Cli\Process\Supervisor\SupervisorConfig;
use Charcoal\Cli\Process\Traits\CrashRecoverableTrait;
use Charcoal\Cli\Process\Traits\SupervisorProcessTrait;

final class TestIpcMasterProcess extends AbstractCliProcess implements
    IpcServerInterface,
    SupervisorInterface,
    CrashRecoverableProcessInterface
{
    use IpcServerTrait;
    use SupervisorProcessTrait;
    use CrashRecoverableTrait;

    protected function onEachTick(): int
    {
        return 1;
    }

    //
    // Recoverable Process Functionality
    //

    protected function declareRecoverableProcess(): ?ProcessRecoveryPolicy
    {
        return new ProcessRecoveryPolicy(
            recoverable: true,
            ticks: 10,
            ticksInterval: (int)1e6,
        );
    }

    //
    // IPC Server Functionality
    //

    public function ipcFrameCodeFrom(int $frameCode): TestDaemonFrames
    {
        return TestDaemonFrames::from($frameCode);
    }

    protected function declareIpcSocketBinding(): TestIpcServices
    {
        return TestIpcServices::Daemon;
    }

    /**
     * @throws \Exception
     */
    protected function handleIpcFrame(MessageFrame $frame): void
    {
        if ($frame->frameCode === TestDaemonFrames::Ping) {
            throw new \Exception("Ping Exception");
        }
    }

    //
    // Supervisor Server Functionality
    //

    protected function declareSupervisorConfig(): SupervisorConfig
    {
        return new SupervisorConfig(maxChildren: 1);
    }

    protected function prepareChildProcess(): void
    {
    }
}
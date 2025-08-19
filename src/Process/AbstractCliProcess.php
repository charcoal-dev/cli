<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process;

use Charcoal\Cli\Console;
use Charcoal\Cli\Contracts\CrashRecoverableProcessInterface;
use Charcoal\Cli\Contracts\Ipc\IpcServerInterface;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Process\Exceptions\UnrecoverableException;
use Charcoal\Cli\Process\Traits\CrashRecoverableTrait;
use Charcoal\Cli\Script\AbstractCliScript;

/**
 * @todo Working in progress
 */
abstract class AbstractCliProcess extends AbstractCliScript
{
    use CrashRecoverableTrait;

    final protected const int TIME_LIMIT = 0;

    /**
     * @param Console $cli
     * @param ExecutionState $initialState
     */
    public function __construct(
        Console        $cli,
        ExecutionState $initialState = ExecutionState::STARTED,
    )
    {
        parent::__construct($cli, $initialState);
    }

    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
        if ($this instanceof CrashRecoverableProcessInterface) {
            $this->recoveryOnConstructHook();
        }

        if ($this instanceof IpcServerInterface) {
            $this->ipcServerOnConstructHook();
        }
    }

    /**
     * Execution logic for every tick, return number of seconds to sleep until next interval
     * @return int
     */
    abstract protected function onEachTick(): int;

    /**
     * @return void
     * @throws \Throwable
     */
    final function execScript(): void
    {
        while (true) {
            try {
                $interval = $this->onEachTick();
                $this->cli->onEveryLoop();
                $this->safeSleep(max($interval, 1));
            } catch (\Throwable $t) {
                $this->handleProcessCrash($t);
            }
        }
    }

    /**
     * @param \Throwable $t
     * @return void
     * @throws UnrecoverableException
     * @throws \Throwable
     */
    protected function handleProcessCrash(\Throwable $t): void
    {
        $this->print("")->print("{red}{b}Process has crashed!")
            ->print(sprintf("{red}[{yellow}%s{/}{red}]: %s{/}", get_class($t), $t->getMessage()));

        // Todo: Update process state to CRASHED
        // Todo: Raise an alert

        // Check for recovery options after a crash...
        if ($t instanceof UnrecoverableException) {
            throw $t;
        }

        // CrashRecoverableProcessInterface
        $recoverable = false;
        if ($this instanceof CrashRecoverableProcessInterface) {
            if ($this->isRecoverable()) {
                $recoverable = true;
            }
        }

        if (!$recoverable) {
            throw $t;
        }

        // Todo: Update logger with exception
        $this->handleRecoveryAfterCrash();
    }

    /**
     * @param bool $compact
     * @return void
     */
    final protected function memoryCleanup(bool $compact = true): void
    {
        call_user_func([$this, $compact ? "print" : "inline"], "{cyan}Runtime memory clean-up initiated: ");
        $this->memoryCleanupHook($compact);
        if (!$compact) {
            $this->print("{green}Done");
        }
    }

    /**
     * @param bool $compact
     * @return void
     */
    protected function memoryCleanupHook(bool $compact = true): void
    {
    }
}
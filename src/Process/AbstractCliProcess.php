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
use Charcoal\Cli\Contracts\Supervisor\SupervisorInterface;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Process\Exceptions\ChildProcessCompletedException;
use Charcoal\Cli\Process\Exceptions\UnrecoverableException;
use Charcoal\Cli\Process\Traits\CrashRecoverableTrait;
use Charcoal\Cli\Script\AbstractCliScript;

/**
 * Abstract class serving as a base implementation for Command Line Interface (CLI) processes. Provides essential
 * mechanisms for structured lifecycle management, error handling, and recovery features for CLI-based systems.
 * This class must be extended and requires an implementation of the `onEachTick` abstract method, which defines
 * the execution logic for every cycle.
 */
abstract class AbstractCliProcess extends AbstractCliScript
{
    use CrashRecoverableTrait;

    final protected const int TIME_LIMIT = 0;

    public function __construct(Console $cli)
    {
        parent::__construct($cli);
        if ($this instanceof CrashRecoverableProcessInterface) {
            $this->recoveryOnConstructHook();
        }

        if ($this instanceof IpcServerInterface) {
            $this->ipcOnConstructHook();
        }

        if ($this instanceof SupervisorInterface) {
            $this->supervisorOnConstructHook();
        }
    }

    /**
     * Execution logic for every tick, return number of seconds to sleep until the next interval
     * @return int
     */
    abstract protected function onEachTick(): int;

    /**
     * @throws \Throwable
     */
    final function exec(): void
    {
        while (true) {
            try {
                $interval = $this->onEachTick();
                $this->onEveryLoop();

                if ($this instanceof SupervisorInterface) {
                    $this->waitChildren(false);
                }

                $this->safeSleep(max($interval, 1));
            } catch (\Throwable $t) {
                if ($this instanceof SupervisorInterface
                    && $t instanceof ChildProcessCompletedException) {
                    break;
                }

                $this->handleProcessCrash($t);
            }
        }

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if($this instanceof SupervisorInterface) {
            $this->terminateChildren(15);
        }
    }

    /**
     * @throws UnrecoverableException
     * @throws \Throwable
     */
    protected function handleProcessCrash(\Throwable $t): void
    {
        $this->print("")->print("{red}{b}Process has crashed!")
            ->print(sprintf("{red}[{yellow}%s{/}{red}]: %s{/}", get_class($t), $t->getMessage()));

        $this->state = ExecutionState::ERROR;
        // Todo: Update process state to CRASHED
        // Todo: Raise an alert

        if ($this instanceof SupervisorInterface) {
            $this->terminateChildren(15);
        }

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
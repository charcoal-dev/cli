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
use Charcoal\Cli\Script\AbstractCliScript;

/**
 * Abstract class serving as a base implementation for Command Line Interface (CLI) processes. Provides essential
 * mechanisms for structured lifecycle management, error handling, and recovery features for CLI-based systems.
 * This class must be extended and requires an implementation of the `onEachTick` abstract method, which defines
 * the execution logic for every cycle.
 */
abstract class AbstractCliProcess extends AbstractCliScript
{
    final protected const int TIME_LIMIT = 0;

    private(set) int $currentTickNum = -1;

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
    final protected function exec(): void
    {
        while (true) {
            $this->currentTickNum++;

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
        if ($this instanceof SupervisorInterface) {
            $this->terminateChildren(15);
        }
    }

    /**
     * @throws UnrecoverableException
     * @throws \Throwable
     */
    protected function handleProcessCrash(\Throwable $t): void
    {
        $this->eol()->print("{red}{b}Process has crashed!")
            ->print(sprintf("{red}[{yellow}%s{/}{red}]: %s{/}", get_class($t), $t->getMessage()));

        $this->state = ExecutionState::ERROR;
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

        $this->state = ExecutionState::HEALING;
        $this->onStartRecovery();

        $this->eol()->print(sprintf("{grey}Recovery expected in {b}%s{/} seconds",
            round(($this->recovery->ticks * $this->recovery->ticksInterval) / $this->recovery->ticksInterval, 1)));
        $this->inline(sprintf("{grey}Recovery in {b}%d{/} ticks ", $this->recovery->ticks));
        for ($i = 0; $i < $this->recovery->ticks; $i++) {
            if (($i % 3) === 0) { // On every 3rd tick
                $this->cli->catchPcntlSignal();
            }

            usleep($this->recovery->ticksInterval);
            $this->inline(".");
        }

        $this->eol()->eol();
        $this->onEndRecovery();
        $this->state = ExecutionState::RUNNING;
    }

    /**
     * @param bool $verbose
     * @return void
     */
    final protected function memoryCleanup(bool $verbose = true): void
    {
        call_user_func([$this, $verbose ? "print" : "inline"], "{cyan}Runtime memory clean-up initiated: ");
        $this->memoryCleanupHook($verbose);
        if (!$verbose) {
            $this->print("{green}Done");
        }
    }

    /**
     * @param bool $verbose
     * @return void
     */
    protected function memoryCleanupHook(bool $verbose = true): void
    {
    }
}
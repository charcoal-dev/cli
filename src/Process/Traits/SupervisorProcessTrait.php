<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process\Traits;

use Charcoal\Cli\Process\AbstractCliProcess;
use Charcoal\Cli\Process\Supervisor\SupervisorConfig;
use Charcoal\Cli\Process\Exceptions\ChildProcessCompletedException;
use Charcoal\Cli\Process\Exceptions\ChildSpawnException;
use Charcoal\Cli\Process\Exceptions\UnrecoverableException;

/**
 * @mixin AbstractCliProcess
 */
trait SupervisorProcessTrait
{
    protected SupervisorConfig $supervisorConfig;
    protected ?array $supervisorChildren = [];

    abstract protected function declareSupervisorConfig(): SupervisorConfig;

    /**
     * This method is called when the process is forked as CHILD.
     * Use it to unset any unnecessary properties or further distinguish $this as a child process.
     */
    abstract protected function prepareChildProcess(): void;

    /**
     * This method is called internally when a child process exits.
     * Can be used to spawn a new child process to maintain count.
     */
    protected function childProcessExitedHook(int $pid, int $status): void
    {
    }

    /**
     * @return void
     */
    public function supervisorOnConstructHook(): void
    {
        $this->supervisorConfig = $this->declareSupervisorConfig();
    }

    /**
     * @throws ChildProcessCompletedException
     * @throws ChildSpawnException
     * @throws UnrecoverableException
     */
    public function spawnChildProcess(callable $logic, array $args): int
    {
        if (!extension_loaded("pcntl")) {
            throw new \RuntimeException("Charcoal supervisor process requires PCNTL extension");
        } elseif (!is_array($this->supervisorConfig)) {
            throw new \RuntimeException("This is not a master process; Cannot spawn child");
        }

        if ($this->supervisorConfig->maxChildren > 0) {
            if (count($this->supervisorChildren ?? []) >= $this->supervisorConfig->maxChildren) {
                throw new ChildSpawnException(sprintf("Maximum number of child-processes reached (%d)",
                    $this->supervisorConfig->maxChildren));
            }
        }

        $childPid = pcntl_fork();
        if ($childPid === -1) {
            throw new ChildSpawnException("Failed to spawn a child-process");
        }

        if ($childPid > 0) {
            // Master Process
            $this->supervisorChildren[$childPid] = true;
            return $childPid;
        }

        // Child Process
        $this->supervisorChildren = null;
        $this->prepareChildProcess();

        // Execute Logic
        try {
            $result = $logic(...$args);
            throw new ChildProcessCompletedException($result);
        } catch (ChildProcessCompletedException $e) {
            throw $e;
        } catch (\Throwable $t) {
            throw new UnrecoverableException(
                sprintf("Child-process %d failed with %s: %s", getmypid(), $t::class, $t->getMessage()),
                previous: $t
            );
        }
    }

    /**
     * @param bool $blocking
     * @return void
     */
    public function waitChildren(bool $blocking = false): void
    {
        if (!extension_loaded("pcntl")) {
            return;
        }

        if ($this->supervisorChildren === null) {
            return;
        }

        foreach (array_keys($this->supervisorChildren) as $childPid) {
            $res = pcntl_waitpid($childPid, $status, $blocking ? 0 : WNOHANG);
            if ($res > 0 || $res === -1) {
                unset($this->supervisorChildren[$childPid]);
                $this->childProcessExitedHook($childPid, $status);
            }
        }
    }

    /**
     * @param int $sigId
     * @return void
     */
    public function terminateChildren(int $sigId): void
    {
        if (!extension_loaded("posix")) {
            throw new \RuntimeException("Charcoal supervisor process requires POSIX extension");
        }

        if ($this->supervisorChildren !== null) {
            foreach (array_keys($this->supervisorChildren) as $workerPid) {
                posix_kill($workerPid, $sigId);
            }

            // Wait for children to terminate
            $this->waitChildren(true);
        }

        if ($this->supervisorChildren === null) {
            $this->print(sprintf("{cyan}Child Process{/} with PID {blue}%d{/} terminated by signal: {red}%d{/}",
                getmypid(), $sigId));
            exit(0);
        }
    }
}
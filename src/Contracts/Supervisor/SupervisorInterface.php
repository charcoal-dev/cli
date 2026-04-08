<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts\Supervisor;

use Charcoal\Cli\Process\Supervisor\SupervisorConfig;

/**
 * Represents an interface for a supervisor entity designed to manage child processes.
 * This includes configuration management, child process lifecycle handling,
 * and initialization tasks specific to the supervisor's setup.
 */
interface SupervisorInterface
{
    /**
     * The supervisor's configuration object.
     */
    protected(set) SupervisorConfig $supervisorConfig {
        set;
        get;
    }

    /**
     * List of currently active child process PIDs managed by the supervisor.
     * Returns null in the child process context.
     */
    protected(set) ?array $supervisorChildren {
        get;
    }

    /**
     * Initializes the supervisor, typically by declaring its configuration.
     * This hook is called during the object construction of a supervisor-aware process.
     */
    public function supervisorOnConstructHook(): void;

    /**
     * Spawns a new child process to execute the given logic.
     */
    public function spawnChildProcess(callable $logic, array $args): int;

    /**
     * Sends a termination signal to all currently managed child processes and waits for them to exit.
     */
    public function terminateChildren(int $sigId): void;

    /**
     * Reaps any child processes that have exited to prevent zombie processes.
     */
    public function waitChildren(bool $blocking = false): void;
}
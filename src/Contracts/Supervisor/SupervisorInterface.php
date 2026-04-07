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
    protected(set) SupervisorConfig $supervisorConfig {
        set;
        get;
    }

    protected(set) ?array $supervisorChildren {
        get;
    }

    public function supervisorOnConstructHook(): void;

    public function spawnChildProcess(callable $logic, array $args): int;

    public function terminateChildren(int $sigId): void;
}
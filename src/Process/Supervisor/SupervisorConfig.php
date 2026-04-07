<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process\Supervisor;

/**
 * Represents the configuration for a supervisor system,
 * defining the maximum number of child processes allowed.
 */
final readonly class SupervisorConfig
{
    public function __construct(
        public int $maxChildren = 10
    )
    {
    }
}
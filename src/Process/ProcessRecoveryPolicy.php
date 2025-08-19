<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process;

/**
 * Represents a policy for process recovery, defining whether recovery is possible
 * and specifying timing parameters.
 */
readonly class ProcessRecoveryPolicy
{
    public function __construct(
        public bool $recoverable,
        public int  $ticks = 300,
        public int  $ticksInterval = 1000000,
    )
    {
    }
}
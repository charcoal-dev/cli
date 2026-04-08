<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Events\State;

use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Events\ConsoleEventSignal;

/**
 * Represents a change in runtime status during execution, implemented as a signal for console events.
 */
final readonly class RuntimeStatusChange implements ConsoleEventSignal
{
    public function __construct(
        public ExecutionState $state,
        public ?string        $scriptFqcn = null,
        public ?\Throwable    $exception = null
    )
    {
    }
}
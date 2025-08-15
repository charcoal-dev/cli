<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Events;

use Charcoal\Events\Contracts\BehaviourContextEnablerInterface;
use Charcoal\Events\Contracts\EventContextInterface;

/**
 * Interface LifecycleEventContext
 * @package Charcoal\Cli\Events
 */
interface ConsoleEventSignal extends EventContextInterface,
    BehaviourContextEnablerInterface
{
}
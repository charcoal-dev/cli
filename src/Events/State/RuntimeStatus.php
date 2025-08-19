<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Events\State;

/**
 * Class ExecutionState
 * @package Charcoal\Cli\Events\State
 */
enum RuntimeStatus
{
    case Prepare;
    case ScriptNotFound;
    case Ready;
    case Completed;
}
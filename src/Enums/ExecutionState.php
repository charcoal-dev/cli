<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Enums;

use Charcoal\Base\Enums\Traits\EnumMappingTrait;

/**
 * Class ExecutionState
 * @package Charcoal\Cli\Enums
 */
enum ExecutionState: string
{
    case STARTED = "started";
    case READY = "ready";
    case RUNNING = "running";
    case PAUSED = "paused";
    case ERROR = "error";
    case HEALING = "healing";
    case STOPPED = "stopped";
    case FINISHED = "finished";
    case UNKNOWN = "unknown";

    use EnumMappingTrait;
}
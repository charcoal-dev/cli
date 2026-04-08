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
    case Initializing = "init";
    case Failed = "failed";
    case Ready = "ready";
    case Running = "running";
    case Paused = "paused";
    case Healing = "healing";
    case Error = "error";
    case Finished = "finished";
    case Unknown = "unknown";

    use EnumMappingTrait;
}
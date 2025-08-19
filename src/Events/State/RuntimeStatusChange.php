<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Events\State;

use Charcoal\Cli\Events\ConsoleEventSignal;
use Charcoal\Cli\Script\AbstractCliScript;

/**
 * Class ExecutionStateChange
 * @package Charcoal\Cli\Events\State
 */
readonly class RuntimeStatusChange implements ConsoleEventSignal
{
    /**
     * @param RuntimeStatus $state
     * @param class-string<AbstractCliScript>|null $scriptClassname
     * @param bool|null $isSuccess
     */
    public function __construct(
        public RuntimeStatus $state,
        public ?string       $scriptClassname = null,
        public ?bool         $isSuccess = null,
    )
    {
    }
}
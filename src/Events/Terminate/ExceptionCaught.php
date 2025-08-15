<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Events\Terminate;

use Charcoal\Cli\Events\ConsoleEventSignal;

/**
 * Class ExceptionCaught
 * @package Charcoal\Cli\Events\Terminate
 */
readonly class ExceptionCaught implements ConsoleEventSignal
{
    public function __construct(
        public \Throwable $exception
    )
    {
    }
}
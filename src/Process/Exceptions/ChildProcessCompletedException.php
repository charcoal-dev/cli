<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process\Exceptions;

/**
 * An exception class indicating that a child process has completed its execution.
 */
final class ChildProcessCompletedException extends \Exception
{
    public function __construct(public readonly mixed $result)
    {
        parent::__construct();
    }
}
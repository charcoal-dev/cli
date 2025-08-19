<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process\Exceptions;

/**
 * Represents an exception that cannot be recovered from and
 * indicates a critical error in the application.
 */
class UnrecoverableException extends \Exception
{
}
<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Ipc\Exceptions;

/**
 * Represents an exception that occurs during an IPC (Inter-Process Communication) socket write operation.
 * This exception is thrown when a failure happens while attempting to write data to a socket.
 */
class IpcSocketWriteException extends \Exception
{
}
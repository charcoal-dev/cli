<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Ipc\Exceptions;

/**
 * Represents an exception that occurs during the reading process from an IPC (Inter-Process Communication) socket.
 * This exception is typically thrown when a read operation fails, encounters an unexpected condition,
 * or cannot proceed as expected in IPC socket communication.
 */
class IpcSocketReadException extends \Exception
{
}
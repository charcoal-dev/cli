<?php
declare(strict_types=1);

namespace Charcoal\Cli\Ipc;

/**
 * Represents an inter-process communication (IPC) message, encapsulating
 * a message string and an address. This class is immutable and ensures
 * that the assigned values cannot be altered after instantiation.
 *
 * The purpose of this class is to provide a straightforward and reliable
 * structure for transporting data in IPC scenarios.
 */
readonly class IpcMessage
{
    public function __construct(
        public string $message,
        public string $address,
    )
    {
    }
}
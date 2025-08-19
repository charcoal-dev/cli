<?php
declare(strict_types=1);

namespace Charcoal\Cli\Ipc;

/**
 * A configuration class for IPC socket settings. This class is immutable.
 * The configuration includes the path to the socket file and the size of datagrams.
 * The datagram size has a default value of 1024 bytes.
 */
final readonly class IpcSocketConfig
{
    public function __construct(
        public string $socketFile,
        public int    $dataGramSize = 1024
    )
    {
    }
}
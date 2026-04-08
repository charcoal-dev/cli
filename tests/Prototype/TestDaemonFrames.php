<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Tests\Prototype;

use Charcoal\Cli\Contracts\Ipc\IpcFrameEnumInterface;

/**
 * An enumeration representing different frame types for a daemon process.
 */
enum TestDaemonFrames: int implements IpcFrameEnumInterface
{
    case Ping = 1;
    case Shutdown = 2;

    public function getCode(): int
    {
        return $this->value;
    }
}
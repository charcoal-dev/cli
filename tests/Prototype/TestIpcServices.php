<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Tests\Prototype;

use Charcoal\Cli\Contracts\Ipc\IpcServiceEnumInterface;
use Charcoal\Cli\Ipc\IpcSocketConfig;

/**
 * Enum representing the different Inter-Process Communication (IPC) services.
 */
enum TestIpcServices: string implements IpcServiceEnumInterface
{
    case Daemon = "/etc/sock/app/daemon.sock";

    public function getConfig(): IpcSocketConfig
    {
        return match ($this) {
            self::Daemon => new IpcSocketConfig($this->value, 1024, blocking: false, encoding: null)
        };
    }
}
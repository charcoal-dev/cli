<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts\Ipc;

use Charcoal\Cli\Ipc\IpcSocket;

/**
 * Interface for defining the structure and behavior of an IPC (Inter-Process Communication) server.
 */
interface IpcServerInterface
{
    public function ipcOnConstructHook(): void;

    public function ipcSocket(): IpcSocket;

    public function ipcEnum(): IpcServiceEnumInterface;

    public function ipcFrameCodeFrom(int $frameCode): IpcFrameEnumInterface;
}
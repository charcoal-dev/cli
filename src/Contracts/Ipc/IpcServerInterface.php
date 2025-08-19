<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts\Ipc;

use Charcoal\Cli\Ipc\IpcSocket;

/**
 * Interface IpcServerInterface
 * @package Charcoal\Cli\Contracts\Ipc
 */
interface IpcServerInterface
{
    public function ipcServerOnConstructHook(): void;

    public function ipcSocket(): IpcSocket;

    public function ipcServiceEnum(): IpcServiceEnumInterface;
}
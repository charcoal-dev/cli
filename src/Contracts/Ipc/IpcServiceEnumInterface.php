<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts\Ipc;

use Charcoal\Cli\Ipc\IpcSocketConfig;

/**
 * Represents a contract for enumerations that provide IPC (Inter-Process Communication) socket configurations.
 */
interface IpcServiceEnumInterface extends \BackedEnum
{
    public function getConfig(): IpcSocketConfig;
}
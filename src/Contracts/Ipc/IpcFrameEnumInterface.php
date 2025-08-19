<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts\Ipc;

/**
 * Represents an interface defining a contract for enumerations in IPC (Inter-Process Communication) frames.
 * Implementing classes is required to provide a method for retrieving a unique integer code associated
 * with the enumeration value.
 */
interface IpcFrameEnumInterface extends \UnitEnum
{
    public function getCode(): int;
}
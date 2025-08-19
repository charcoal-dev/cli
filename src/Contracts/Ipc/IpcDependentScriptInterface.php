<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts\Ipc;

/**
 * Defines the structure for scripts that depend on inter-process communication (IPC) services.
 */
interface IpcDependentScriptInterface
{
    public function ipcDependsOn(): IpcServiceEnumInterface;

    public function waitForIpcService(
        IpcServiceEnumInterface $dependsOn,
        string                  $whoAmI,
        int                     $interval = 3,
        int                     $maxAttempts = 300
    ): void;
}
<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Ipc\Traits;

use Charcoal\Cli\Contracts\Ipc\IpcServerInterface;
use Charcoal\Cli\Contracts\Ipc\IpcServiceEnumInterface;
use Charcoal\Cli\Ipc\Exceptions\IpcSocketReadException;
use Charcoal\Cli\Ipc\IpcSocket;
use Charcoal\Cli\Ipc\MessageFrame;
use Charcoal\Cli\Process\AbstractCliProcess;

/**
 * @mixin AbstractCliProcess
 */
trait IpcServerTrait
{
    protected readonly IpcSocket $ipcSocket;
    protected readonly IpcServiceEnumInterface $ipcEnum;

    abstract protected function declareIpcSocketBinding(): IpcServiceEnumInterface;

    abstract protected function handleIpcFrame(MessageFrame $frame): void;

    /**
     * @return void
     */
    public function ipcOnConstructHook(): void
    {
        $this->inline("{cyan}Starting IPC server{/} {grey}...");
        $this->ipcEnum = $this->declareIpcSocketBinding();
        $socketConfig = $this->ipcEnum->getConfig();
        $this->ipcSocket = new IpcSocket($socketConfig);
        $this->print(" {magenta}" . basename($socketConfig->socketFile));
    }

    /**
     * @return IpcSocket
     */
    public function ipcSocket(): IpcSocket
    {
        return $this->ipcSocket;
    }

    /**
     * @return IpcServiceEnumInterface
     */
    public function ipcEnum(): IpcServiceEnumInterface
    {
        return $this->ipcEnum;
    }

    /**
     * @param bool $quiet
     * @return void
     */
    protected function ipcListen(bool $quiet = false): void
    {
        if (!$quiet) {
            $this->inline("Reading {yellow}{invert} IPC {/} message(s) {grey}... ");
        }

        try {
            $msgQueue = $this->ipcSocket->receive();
        } catch (IpcSocketReadException $e) {
            if (!$quiet) {
                $this->print("");
                $this->print("{red}Failed to read IPC socket");
                $this->print("\t{red}" . $e->getMessage());
            }
            return;
        }

        if (!$quiet) {
            $this->print("{invert}{yellow} " . count($msgQueue) . " {/}");
        }

        if ($msgQueue) {
            foreach ($msgQueue as $msg) {
                $ipcFrame = null;

                try {
                    /** @var IpcServerInterface $this */
                    $ipcFrame = MessageFrame::decode($this, $msg->message);
                } catch (\Throwable $t) {
                    if (!$quiet) {
                        $this->print("{red}* Invalid message received");
                        $this->print("\t{grey}[" . get_class($t) . "]: " . $t->getMessage());
                    }
                }

                if ($ipcFrame) {
                    if (!$quiet) {
                        $this->inline(sprintf("\t[{magenta}%s{/}]: ", $ipcFrame->frameCode->name));
                    }

                    $this->handleIpcFrame($ipcFrame);
                    if (!$quiet) {
                        $this->print("");
                    }
                }
            }
        }
    }
}
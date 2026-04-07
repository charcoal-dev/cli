<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Ipc\Traits;

use Charcoal\Cli\Contracts\Ipc\IpcFrameEnumInterface;
use Charcoal\Cli\Contracts\Ipc\IpcServerInterface;
use Charcoal\Cli\Contracts\Ipc\IpcServiceEnumInterface;
use Charcoal\Cli\Ipc\IpcSocket;
use Charcoal\Cli\Ipc\IpcSocketConfig;
use Charcoal\Cli\Ipc\MessageFrame;
use Charcoal\Cli\Script\AbstractCliScript;
use Charcoal\Contracts\Buffers\ReadableBufferInterface;

/**
 * @mixin AbstractCliScript
 */
trait IpcClientTrait
{
    private ?IpcSocket $ipcClientSocket = null;

    /**
     * @throws \Charcoal\Cli\Ipc\Exceptions\IpcSocketWriteException
     */
    protected function ipcDispatch(
        IpcServiceEnumInterface|IpcSocketConfig $recipient,
        MessageFrame                            $message
    ): void
    {
        if ($recipient instanceof IpcServiceEnumInterface) {
            $recipient = $recipient->getConfig();
        }

        if ($this instanceof IpcServerInterface) {
            $socket = $this->ipcSocket();
        } else {
            $this->ipcClientSocket ??= new IpcSocket(null);
            $socket = $this->ipcClientSocket;
        }

        $socket->send($recipient, $message->encode()->bytes());
    }

    /**
     * @param string $message
     * @param IpcFrameEnumInterface $frameCode
     * @param ReadableBufferInterface|null $data
     * @return MessageFrame
     */
    protected function ipcPrepareFrame(
        string                   $message,
        IpcFrameEnumInterface    $frameCode,
        ?ReadableBufferInterface $data
    ): MessageFrame
    {
        return new MessageFrame(
            $message,
            getmypid(),
            $this instanceof IpcServerInterface ? $this->ipcEnum() : null,
            $frameCode,
            $data
        );
    }
}
<?php
declare(strict_types=1);

namespace Charcoal\Cli\Ipc;

use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Cli\Ipc\Exceptions\IpcSocketReadException;
use Charcoal\Cli\Ipc\Exceptions\IpcSocketWriteException;

/**
 * Represents an Inter-Process Communication (IPC) socket used for sending and receiving
 * datagram-based messages over a UNIX domain socket.
 */
class IpcSocket
{
    use NotCloneableTrait;
    use NotSerializableTrait;
    use NoDumpTrait;

    private ?\Socket $socket = null;

    /**
     * @param IpcSocketConfig|null $config
     */
    public function __construct(
        public readonly ?IpcSocketConfig $config
    )
    {
        if ($this->config) {
            @unlink($this->config->socketFile);
            $this->socket = $this->createClientSocket();
            if (!socket_bind($this->socket, $this->config->socketFile)) {
                throw new \RuntimeException("Failed to bind IPC socket: " .
                    socket_strerror(socket_last_error($this->socket)));
            }
        }
    }

    /**
     * @throws IpcSocketWriteException
     */
    public function send(IpcSocketConfig $recipient, string $message): void
    {
        $sender = $this->socket ?? $this->createClientSocket();
        if ($recipient->encoding) {
            $message = $recipient->encoding->encode($message);
        }

        $messageLen = strlen($message);
        if ($messageLen > $recipient->dataGramSize) {
            throw new \OverflowException(
                "Message exceeds recipient datagram size of " . $recipient->dataGramSize . " bytes"
            );
        }

        if (!@socket_sendto($sender, $message, $messageLen, 0, $recipient->socketFile)) {
            $error = socket_last_error($sender);
            if ($error === SOCKET_EAGAIN || $error === SOCKET_EWOULDBLOCK) {
                throw new IpcSocketWriteException("Socket buffer full (EAGAIN/EWOULDBLOCK)", $error);
            }

            throw new IpcSocketWriteException(socket_strerror($error), $error);
        }

        if (!$this->socket) {
            $this->closeClientSocket($sender);
        }
    }

    /**
     * @param int $maxMessages
     * @return IpcMessage[]
     * @throws IpcSocketReadException
     */
    public function receive(int $maxMessages = 100): array
    {
        if (!$this->socket || !$this->config) {
            throw new \LogicException("Cannot receive messages on IpcSocket without IpcSocketBinding");
        }

        $queue = [];
        $readCount = 0;
        while ($readCount < $maxMessages) {
            $msgSender = "";
            $msgBuffer = "";
            $read = @socket_recvfrom($this->socket, $msgBuffer, $this->config->dataGramSize, 0, $msgSender);
            if ($read === false) {
                $ipcSocketError = socket_last_error($this->socket);
                if ($ipcSocketError === SOCKET_EAGAIN || $ipcSocketError === SOCKET_EWOULDBLOCK) {
                    break;
                }

                throw new IpcSocketReadException(socket_strerror($ipcSocketError), $ipcSocketError);
            }

            if ($this->config->encoding) {
                $msgBuffer = $this->config->encoding->decode($msgBuffer);
            }

            $queue[] = new IpcMessage($msgBuffer, $msgSender);
            $readCount++;
        }

        return $queue;
    }

    /**
     * @param bool|null $blocking
     * @return \Socket
     */
    public function createClientSocket(?bool $blocking = null): \Socket
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new \RuntimeException("Failed to create socket: " .
                socket_strerror(socket_last_error()));
        }

        $blocking ??= $this->config?->blocking ?? false;
        if (!$blocking) {
            if (!socket_set_nonblock($socket)) {
                throw new \RuntimeException("Failed to set IPC socket in NON-BLOCK mode. " .
                    socket_strerror(socket_last_error($socket)));
            }
        } else {
            if (!socket_set_block($socket)) {
                throw new \RuntimeException("Failed to set IPC socket in BLOCK mode. " .
                    socket_strerror(socket_last_error($socket)));
            }
        }

        return $socket;
    }

    /**
     * @param \Socket $socket
     * @return void
     */
    public function closeClientSocket(\Socket $socket): void
    {
        socket_close($socket);
    }

    /**
     * Closes socket connection on destruct
     */
    public function __destruct()
    {
        if ($this->socket) {
            $this->closeClientSocket($this->socket);
            $this->socket = null;
        }
    }
}
<?php
declare(strict_types=1);

namespace Charcoal\Cli\Ipc;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Cli\Ipc\Exceptions\IpcSocketReadException;
use Charcoal\Cli\Ipc\Exceptions\IpcSocketWriteException;

/**
 * Class IpcSocket
 * @package App\Shared\Core\Ipc
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
        $messageLen = strlen($message);
        if ($messageLen > $recipient->dataGramSize) {
            throw new \OverflowException(
                "Message exceeds recipient datagram size of " . $recipient->dataGramSize . " bytes"
            );
        }

        $sender = $this->socket ?? $this->createClientSocket();
        if (!@socket_sendto($sender, $message, $messageLen, 0, $recipient->socketFile)) {
            $error = socket_last_error($sender);
            throw new IpcSocketWriteException(socket_strerror($error), $error);
        }

        if (!$this->socket) {
            $this->closeClientSocket($sender);
        }
    }

    /**
     * @throws IpcSocketReadException
     */
    public function receive(): array
    {
        if (!$this->socket || !$this->config) {
            throw new \LogicException("Cannot receive messages on IpcSocket without IpcSocketBinding");
        }

        $queue = [];
        while (true) {
            $msgSender = "";
            $msgBuffer = "";
            $read = socket_recvfrom($this->socket, $msgBuffer, $this->config->dataGramSize, 0, $msgSender);
            if ($read === false) {
                $ipcSocketError = socket_last_error($this->socket);
                if ($ipcSocketError === SOCKET_EAGAIN || $ipcSocketError === SOCKET_EWOULDBLOCK) {
                    break;
                }

                throw new IpcSocketReadException(socket_strerror($ipcSocketError), $ipcSocketError);
            }

            $queue[] = new IpcMessage($msgBuffer, $msgSender);
        }

        return $queue;
    }

    /**
     * @return \Socket
     */
    public function createClientSocket(): \Socket
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new \RuntimeException("Failed to create socket: " .
                socket_strerror(socket_last_error()));
        }

        if (!socket_set_nonblock($socket)) {
            throw new \RuntimeException("Failed to set IPC client socket in NON-BLOCK mode. " .
                socket_strerror(socket_last_error($socket)));
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
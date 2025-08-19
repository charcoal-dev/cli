<?php
declare(strict_types=1);

namespace Charcoal\Cli\Ipc;

use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\ByteReader;
use Charcoal\Cli\Contracts\Ipc\IpcFrameEnumInterface;
use Charcoal\Cli\Contracts\Ipc\IpcServiceEnumInterface;

/**
 * Class AppIpcFrame
 * @package App\Shared\Core\Ipc
 */
class MessageFrame
{
    public const string IDENTIFIER_BYTES = "\xc\x7\x8\x6";
    public const int CHUNK_SIZE = 32;

    /**
     * @param string $message
     * @param int $pid
     * @param IpcServiceEnumInterface|null $senderServiceEnum
     * @param IpcFrameEnumInterface $frameCode
     * @param Buffer|null $data
     */
    public function __construct(
        public readonly string                   $message,
        public readonly int                      $pid,
        public readonly ?IpcServiceEnumInterface $senderServiceEnum,
        public readonly IpcFrameEnumInterface    $frameCode,
        public readonly ?Buffer                  $data,
    )
    {
    }

    /**
     * @return Buffer
     */
    public function encode(): Buffer
    {
        $encoded = new Buffer(static::IDENTIFIER_BYTES);
        $encoded->append($this->getPaddedChunk($this->message, "sender"));
        $encoded->appendUInt32LE($this->pid);
        if ($this->senderServiceEnum) {
            $encoded->appendUInt8(1);
            $encoded->append($this->getPaddedChunk($this->senderServiceEnum->value, "senderServiceEnum"));
        } else {
            $encoded->appendUInt8(0);
        }

        $encoded->appendUInt16LE($this->frameCode->getCode());

        $dataLen = $this->data?->len() ?? 0;
        $encoded->appendUInt16LE($dataLen);
        if ($dataLen) {
            $encoded->append($this->data);
        }

        return $encoded->readOnly();
    }

    /**
     * @param string $value
     * @param string $which
     * @return string
     */
    private function getPaddedChunk(string $value, string $which): string
    {
        if (strlen($value) > static::CHUNK_SIZE) {
            throw new \OverflowException('Value for "' . $which . '" cannot exceed chunk size of 32 bytes');
        }

        return str_pad($value, 32, "\0", STR_PAD_LEFT);
    }

    /**
     * @param string $message
     * @return false|ByteReader
     */
    public static function isReadable(string $message): false|ByteReader
    {
        try {
            $buffer = Buffer::fromBase16($message)->read();
            if ($buffer->first(strlen(static::IDENTIFIER_BYTES)) === static::IDENTIFIER_BYTES) {
                return $buffer;
            }
        } catch (\Exception) {
        }

        return false;
    }

    /**
     * @param string $message
     * @return static
     * @todo Finish Implementation
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public static function decode(string $message): static
    {
        $read = static::isReadable($message);
        if (!$read) {
            throw new \DomainException("Buffer not compatible with AppIpcFrame encoding");
        }

        $sender = ltrim($read->next(static::CHUNK_SIZE));
        $pid = $read->readUInt32LE();
        $senderServiceEnum = null;
        if ($read->readUInt8() === 1) {
            $senderServiceEnum = IpcServiceEnumInterface::from(ltrim($read->next(static::CHUNK_SIZE)));
        }

        //$frameCode = IpcFrameEnumInterface::fromFrameCode($read->readUInt16LE());
        $data = null;
        $dataLen = $read->readUInt16LE();
        if ($dataLen > 0) {
            $data = new Buffer($read->next($dataLen));
        }

        if (!$read->isEnd()) {
            throw new \DomainException("Message contains additional bytes");
        }

        //return new static($sender, $pid, $senderServiceEnum, $frameCode, $data);

        throw new \LogicException("Not implemented");
    }
}
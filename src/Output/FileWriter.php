<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Output;

use Charcoal\Cli\Console;
use Charcoal\Filesystem\File;

/**
 * Class FileWriter
 * @package Charcoal\Cli\Output
 */
class FileWriter extends AbstractOutputHandler
{
    /** @var mixed|null */
    private mixed $fp = null;

    /**
     * @param File $file
     * @param bool $append
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function __construct(public readonly File $file, public readonly bool $append)
    {
        if (!$this->file->isWritable()) {
            throw new \UnexpectedValueException('Logger file is not writable');
        }
    }

    /**
     * @param Console|null $cli
     * @return void
     */
    public function startBuffer(?Console $cli = null): void
    {
        if ($cli) {
            $this->useAnsiCodes = $cli->flags->useANSI();
        }

        $this->fp = fopen($this->file->path, $this->append ? "a" : "w");
    }

    /**
     * @return void
     */
    public function endBuffer(): void
    {
        if ($this->fp) {
            fclose($this->fp);
            $this->fp = null;
        }
    }

    /**
     * @return string|null
     */
    public function getBufferedData(): null|string
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return is_resource($this->fp);
    }

    /**
     * @param string $input
     * @param bool $eol
     * @return void
     */
    public function write(string $input, bool $eol): void
    {
        if (!$this->fp) {
            return;
        }

        fwrite($this->fp, $this->getAnsiFilteredString($input, $eol) . ($eol ? $this->eolChar : ""));
    }
}

<?php
/*
 * This file is a part of "charcoal-dev/cli" package.
 * https://github.com/charcoal-dev/cli
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/cli/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\CLI\Console;

use Charcoal\CLI\CLI;
use Charcoal\Filesystem\File;

/**
 * Class FileWriter
 * @package Charcoal\CLI\Console
 */
class FileWriter extends AbstractOutputHandler
{
    /** @var mixed|null */
    private mixed $fp = null;

    /**
     * @param \Charcoal\Filesystem\File $file
     * @param bool $append
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(public readonly File $file, public readonly bool $append)
    {
        if (!$this->file->isWritable()) {
            throw new \UnexpectedValueException('Logger file is not writable');
        }
    }

    public function startBuffer(?CLI $cli = null): void
    {
        if ($cli) {
            $this->useAnsiCodes = $cli->flags->useANSI();
        }

        $this->fp = fopen($this->file->path, $this->append ? "a" : "w");
    }

    public function endBuffer(): void
    {
        fclose($this->fp);
        $this->fp = null;
    }

    public function getBufferedData(): null|string
    {
        return null;
    }

    public function isActive(): bool
    {
        return is_resource($this->fp);
    }

    public function write(string $input, bool $eol): void
    {
        if (!$this->fp) {
            return;
        }

        fwrite($this->fp, $this->getAnsiFilteredString($input, $eol) . ($eol ? $this->eolChar : ""));
    }
}

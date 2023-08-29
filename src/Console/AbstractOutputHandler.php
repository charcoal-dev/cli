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

use Charcoal\CLI\AnsiEscapeSeq;
use Charcoal\CLI\CLI;

/**
 * Class AbstractOutputHandler
 * @package Charcoal\CLI\Console
 */
abstract class AbstractOutputHandler
{
    protected bool $useAnsiCodes = true;
    protected string $eolChar = PHP_EOL;

    /**
     * @param \Charcoal\CLI\CLI $cli
     * @return void
     */
    abstract public function startBuffer(CLI $cli): void;

    /**
     * @param \Charcoal\CLI\CLI $cli
     * @return void
     */
    abstract public function endBuffer(CLI $cli): void;

    /**
     * @return string|null
     */
    abstract public function getBufferedData(): null|string;

    /**
     * @return bool
     */
    abstract public function isActive(): bool;

    /**
     * @param string $input
     * @param bool $eol
     * @return void
     */
    abstract public function write(string $input, bool $eol): void;

    /**
     * @param bool $ansi
     * @return $this
     */
    public function useAnsiCodes(bool $ansi): static
    {
        $this->useAnsiCodes = $ansi;
        return $this;
    }

    /**
     * @param string $eolChar
     * @return $this
     */
    public function useEolChar(string $eolChar): static
    {
        $this->eolChar = $eolChar;
        return $this;
    }

    /**
     * @param string $input
     * @return string
     */
    protected function getAnsiFilteredString(string $input): string
    {
        return $this->useAnsiCodes ?
            AnsiEscapeSeq::Parse($input) : AnsiEscapeSeq::Clean($input);
    }
}

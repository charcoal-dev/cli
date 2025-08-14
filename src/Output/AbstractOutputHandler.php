<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Output;

use Charcoal\Cli\Console;
use Charcoal\Cli\Display\AnsiEscapeSeq;

/**
 * Class AbstractOutputHandler
 * @package Charcoal\Cli\Output
 */
abstract class AbstractOutputHandler
{
    protected bool $useAnsiCodes = true;
    protected string $eolChar = PHP_EOL;

    /**
     * @param Console|null $cli
     * @return void
     */
    abstract public function startBuffer(?Console $cli = null): void;

    /**
     * @return void
     */
    abstract public function endBuffer(): void;

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
     * @param bool $addResetSuffix
     * @return string
     */
    protected function getAnsiFilteredString(string $input, bool $addResetSuffix): string
    {
        return $this->useAnsiCodes ?
            AnsiEscapeSeq::Parse($input, $addResetSuffix) : AnsiEscapeSeq::Clean($input);
    }
}

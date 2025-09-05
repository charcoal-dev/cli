<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Output;

use Charcoal\Cli\Console;

/**
 * Represents a buffer capable of storing output data and controlling its behavior for writing
 * and retrieving content. Extends functionality provided by AbstractOutputHandler.
 */
class StringBuffer extends AbstractOutputHandler
{
    private ?string $buffer = null;
    private ?string $finished = null;

    /**
     * @param Console|null $cli
     * @return void
     */
    public function startBuffer(?Console $cli = null): void
    {
        if ($cli) {
            $this->useAnsiCodes = $cli->flags->useANSI();
        }

        $this->buffer = "";
    }

    /**
     * @return void
     */
    public function endBuffer(): void
    {
        $this->finished = $this->buffer;
        $this->buffer = null;
    }

    /**
     * @return string|null
     */
    public function getBufferedData(): null|string
    {
        return $this->finished ?? $this->buffer;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return is_string($this->buffer);
    }

    /**
     * @param string $input
     * @param bool $eol
     * @return void
     */
    public function write(string $input, bool $eol): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->buffer .= $this->getAnsiFilteredString($input, $eol) .
            ($eol ? $this->eolChar : "");
    }
}

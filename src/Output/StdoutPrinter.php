<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Output;

use Charcoal\Cli\Console;

/**
 * Class StdoutPrinter
 * @package Charcoal\Cli\Output
 */
class StdoutPrinter extends AbstractOutputHandler
{
    public function startBuffer(?Console $cli = null): void
    {
        if($cli) {
            $this->useAnsiCodes = $cli->flags->useANSI();
        }
    }

    public function endBuffer(): void
    {
    }

    public function getBufferedData(): null|string
    {
        return null;
    }

    public function isActive(): bool
    {
        return true;
    }

    public function write(string $input, bool $eol): void
    {
        if (!$this->isActive()) {
            return;
        }

        print $this->getAnsiFilteredString($input, $eol) . ($eol ? $this->eolChar : "");
    }
}

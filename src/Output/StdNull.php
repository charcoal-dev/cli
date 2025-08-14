<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Output;

use Charcoal\Cli\Console;

/**
 * Class StdNull
 * @package Charcoal\Cli\Output
 */
class StdNull extends AbstractOutputHandler
{
    public function startBuffer(?Console $cli = null): void
    {
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
    }
}

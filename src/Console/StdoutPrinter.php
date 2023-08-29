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

/**
 * Class StdoutPrinter
 * @package Charcoal\CLI\Console
 */
class StdoutPrinter extends AbstractOutputHandler
{
    /**
     * @param \Charcoal\CLI\CLI $cli
     * @return void
     */
    public function startBuffer(CLI $cli): void
    {
        $this->useAnsiCodes = $cli->flags->useANSI();
    }

    public function endBuffer(CLI $cli): void
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

    public function write(string $input, bool $eol, int $typewriter = 0): void
    {
        if (!$this->isActive()) {
            return;
        }

        print $this->getAnsiFilteredString($input) . ($eol ? $this->eolChar : "");
    }
}

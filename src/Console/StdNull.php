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
 * Class StdNull
 * @package Charcoal\CLI\Console
 */
class StdNull extends AbstractOutputHandler
{
    public function startBuffer(?CLI $cli = null): void
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

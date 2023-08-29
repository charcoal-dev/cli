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

/**
 * Class AbstractOutputHandler
 * @package Charcoal\CLI\Console
 */
abstract class AbstractOutputHandler implements OutputHandlerInterface
{
    protected bool $useAnsiCodes = true;
    protected string $eolChar = PHP_EOL;

    /**
     * @param bool $ansi
     * @return $this
     */
    public function useAnsiCodes(bool $ansi): static
    {
        $this->useAnsiCodes = $ansi;
        return $this;
    }

    public function useEolChar(string $eolChar): static
    {
        $this->eolChar = $eolChar;
        return $this;
    }
}

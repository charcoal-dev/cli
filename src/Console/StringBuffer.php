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
 * Class StringBuffer
 * @package Charcoal\CLI\Console
 */
class StringBuffer extends AbstractOutputHandler
{
    private ?string $buffer = null;
    private ?string $finished = null;

    /**
     * @param \Charcoal\CLI\CLI $cli
     * @return void
     */
    public function startBuffer(CLI $cli): void
    {
        $this->buffer = "";
    }

    /**
     * @param \Charcoal\CLI\CLI $cli
     * @return void
     */
    public function endBuffer(CLI $cli): void
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

        $this->buffer .= $this->getAnsiFilteredString($input, $eol) . ($eol ? $this->eolChar : "");
    }
}

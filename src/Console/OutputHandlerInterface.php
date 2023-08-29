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
 * Interface OutputHandlerInterface
 * @package Charcoal\CLI\Console
 */
interface OutputHandlerInterface
{
    public function useAnsiCodes(bool $ansi): static;

    public function useEolChar(string $eolChar): static;

    public function write(string $data): void;

    public function isActive(): bool;

    public function startClean(): void;

    public function endClean(): null|string;
}

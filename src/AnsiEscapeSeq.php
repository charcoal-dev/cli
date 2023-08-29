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

namespace Charcoal\CLI;

/**
 * Class AnsiEscapeSeq
 * @package Charcoal\CLI
 */
class AnsiEscapeSeq
{
    /**
     * @param string $input
     * @return string
     */
    public static function Clean(string $input): string
    {
        return preg_replace('/{([a-z]+|\/)}/i', '', $input);
    }

    /**
     * @param string $input
     * @param bool $addResetSuffix
     * @return string
     */
    public static function Parse(string $input, bool $addResetSuffix = true): string
    {
        $parsed = preg_replace_callback(
            '/{([a-z]+|\/)}/i',
            function ($modifier) {
                return match (strtolower($modifier[1] ?? "")) {
                    "red" => "\e[31m",
                    "green" => "\e[32m",
                    "yellow" => "\e[33m",
                    "blue" => "\e[34m",
                    "magenta" => "\e[35m",
                    "gray", "grey" => "\e[90m",
                    "cyan" => "\e[36m",
                    "b", "bold" => "\e[1m",
                    "u", "underline" => "\e[4m",
                    "blink" => "\e[5m",
                    "invert" => "\e[7m",
                    "reset", "/" => "\e[0m",
                    default => $modifier[0] ?? "",
                };
            },
            $input
        );

        if ($addResetSuffix) {
            $parsed .= "\e[0m";
        }

        return $parsed;
    }
}

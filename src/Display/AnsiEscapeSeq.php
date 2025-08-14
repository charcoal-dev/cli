<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Display;

/**
 * Class AnsiEscapeSeq
 * @package Charcoal\Cli\Display
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
            '/{([a-z]+|\/)([0-9]+)*}/i',
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
                    "d", "dim" => "\e[2m",
                    "u", "underline" => "\e[4m",
                    "blink" => "\e[5m",
                    "invert" => "\e[7m",
                    "goleft" => sprintf("\e[%dD", intval($modifier[2] ?? 1)),
                    "goright" => sprintf("\e[%dC", intval($modifier[2] ?? 1)),
                    "goup" => sprintf("\e[%dA", intval($modifier[2] ?? 1)),
                    "godown" => sprintf("\e[%dB", intval($modifier[2] ?? 1)),
                    "atlinestart" => "\e[G",
                    "clearline" => "\e[2K",
                    "trimleft" => "\e[1K",
                    "trimright" => "\e[K",
                    "clearscreen" => "\e[2J",
                    "clearleft" => "\e[1J",
                    "clearright" => "\e[J",
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

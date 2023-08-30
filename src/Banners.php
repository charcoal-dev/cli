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
 * Class Banners
 * @package Charcoal\CLI
 */
class Banners
{
    /**
     * @param string $caption
     * @return static
     */
    public static function Digital(string $caption): static
    {
        $words = explode(" ", $caption);
        $caption = "|" . implode("|", str_split($caption)) . "|";
        foreach ($words as $word) {
            $padding[] = str_repeat("+-", strlen($word)) . "+";
        }

        $padding = implode(" ", $padding ?? []);
        return new static([$padding, $caption, $padding], "digital");
    }

    /**
     * @param array $lines
     * @param string $name
     */
    public function __construct(
        public readonly array  $lines,
        public readonly string $name
    )
    {
    }
}

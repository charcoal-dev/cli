<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Display;

/**
 * Class Banners
 * @package Charcoal\Cli\Display
 */
readonly class Banners
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
        public array  $lines,
        public string $name
    )
    {
    }
}

<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Script;

/**
 * Class Flags
 * @package Charcoal\Cli\Script
 */
class Flags
{
    public const int QUICK = 1 << 0;
    public const int FORCE = 1 << 1;
    public const int DEBUG = 1 << 2;
    public const int VERBOSE = 1 << 3;
    public const int ANSI = 1 << 4;

    /** @var int */
    private int $flags = 0;

    /**
     * @return bool
     */
    public function isQuick(): bool
    {
        return $this->has(static::QUICK);
    }

    /**
     * @return bool
     */
    public function forceExec(): bool
    {
        return $this->has(static::FORCE);
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->has(static::DEBUG);
    }

    /**
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->has(static::VERBOSE);
    }

    /**
     * @return bool
     */
    public function useANSI(): bool
    {
        return $this->has(static::ANSI);
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function set(int $flag): static
    {
        $this->flags = $this->flags | $flag;
        return $this;
    }

    /**
     * @param int $flag
     * @return bool
     */
    public function has(int $flag): bool
    {
        return (bool)(($this->flags & $flag));
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function remove(int $flag): static
    {
        $this->flags &= ~$flag;
        return $this;
    }
}

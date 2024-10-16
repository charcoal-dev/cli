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
 * Class Arguments
 * @package Charcoal\CLI
 */
class Arguments implements \IteratorAggregate
{
    /** @var array */
    private array $args = [];
    /** @var int */
    private int $count = 0;

    /**
     * @param string $name
     * @param string|null $value
     * @return $this
     */
    public function set(string $name, ?string $value = null): self
    {
        $this->args[strtolower($name)] = $value;
        $this->count++;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->args);
    }

    /**
     * @param string $name
     * @return string|false|null
     */
    public function get(string $name): string|null|false
    {
        $key = strtolower($name);
        if (array_key_exists($key, $this->args)) {
            return $this->args[$key];
        }

        return false;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->args);
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->args;
    }
}

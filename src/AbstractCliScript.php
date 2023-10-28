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
 * Class AbstractCliScript
 * @package Charcoal\CLI
 * @method void onSignalCloseCallback(int $sigId)
 */
abstract class AbstractCliScript
{
    /** @var int Set a execution time limit or 0 for infinite */
    protected const TIME_LIMIT = 30;

    public readonly int $timeLimit;

    /**
     * @param \Charcoal\CLI\CLI $cli
     */
    public function __construct(public readonly CLI $cli)
    {
        if (!is_int(static::TIME_LIMIT) || static::TIME_LIMIT < 0) {
            throw new \InvalidArgumentException('Invalid CLI script time limit');
        }

        $this->timeLimit = static::TIME_LIMIT;
    }

    /**
     * @return void
     */
    public function onEveryLoop(): void
    {
        $this->cli->onEveryLoop();
    }

    /**
     * Alias of exec method
     * @return void
     */
    final public function burn(): void
    {
        $this->exec();
    }

    /**
     * @return void
     */
    abstract public function exec(): void;

    /**
     * @param string $line
     * @param int $sleep
     * @return $this
     */
    final protected function print(string $line, int $sleep = 0): static
    {
        $this->cli->print($line, $sleep);
        return $this;
    }

    /**
     * @return $this
     */
    final protected function eol(): static
    {
        $this->cli->eol();
        return $this;
    }

    /**
     * @param string $line
     * @param int $sleep
     * @return $this
     */
    final protected function inline(string $line, int $sleep = 0): static
    {
        $this->cli->inline($line, $sleep);
        return $this;
    }

    /**
     * @param int $milliseconds
     */
    final protected function microSleep(int $milliseconds = 0): void
    {
        $this->cli->microSleep($milliseconds);
    }

    /**
     * @param string $line
     * @param int $interval
     * @param bool $eol
     * @return $this
     */
    final protected function typewriter(string $line, int $interval = 100, bool $eol = false): static
    {
        $this->cli->typewriter($line, $interval, $eol);
        return $this;
    }

    /**
     * @param string $char
     * @param int $count
     * @param int $interval
     * @param bool $eol
     * @return $this
     */
    final protected function repeatChar(string $char = ".", int $count = 10, int $interval = 100, bool $eol = false): static
    {
        $this->cli->repeatChar($char, $count, $interval, $eol);
        return $this;
    }

    /**
     * @param string $line
     * @return string|bool
     */
    final protected function requireInput(string $line): string|bool
    {
        $this->cli->inline(trim($line) . " ");
        return $this->waitForInput();
    }

    /**
     * @return string|bool
     */
    final protected function waitForInput(): string|bool
    {
        return trim(fgets(STDIN));
    }

    /**
     * @return \Charcoal\CLI\Arguments
     */
    final protected function args(): Arguments
    {
        return $this->cli->args;
    }

    /**
     * @return Flags
     */
    final protected function flags(): Flags
    {
        return $this->cli->flags;
    }
}

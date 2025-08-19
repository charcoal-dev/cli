<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Script;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Cli\Console;
use Charcoal\Cli\Contracts\Ipc\IpcDependentScriptInterface;
use Charcoal\Cli\Enums\ExecutionState;

/**
 * Class AbstractCliScript
 * @package Charcoal\Cli\Script
 * @method void onSignalCloseCallback(int $sigId)
 */
abstract class AbstractCliScript
{
    /** @var int Set an execution time limit or 0 for infinite */
    protected const int TIME_LIMIT = 30;

    public readonly int $startedOn;
    public readonly int $timeLimit;
    protected ExecutionState $state;

    /**
     * @param Console $cli
     * @param ExecutionState $initialState
     */
    public function __construct(
        public readonly Console $cli,
        ExecutionState          $initialState = ExecutionState::STARTED)
    {
        if (!is_int(static::TIME_LIMIT) || static::TIME_LIMIT < 0) {
            throw new \InvalidArgumentException('Invalid CLI script time limit');
        }

        $this->timeLimit = static::TIME_LIMIT;
        $this->state = $initialState;

        // Declared Depends On?
        if ($this instanceof IpcDependentScriptInterface) {
            $this->waitForIpcService(
                $this->ipcDependsOn(),
                $this->semaphoreLockId ?? ObjectHelper::baseClassName($this),
                interval: 3,
                maxAttempts: 100
            );
        }
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
     * @param int $timeout
     * @return string
     */
    final protected function requireInput(string $line, int $timeout = 300): string
    {
        $this->cli->inline(trim($line) . " ");
        return $this->waitForInput($timeout);
    }

    /**
     * @param int $timeout
     * @return string
     */
    final protected function waitForInput(int $timeout = 300): string
    {
        $startedOn = time();
        while (true) {
            if ($timeout > 0 && (time() - $startedOn) > $timeout) {
                throw new \RuntimeException("Input stream timed out after $timeout seconds");
            }

            $input = fgets(STDIN);
            if ($input === false) {
                continue;
            }

            $input = trim($input);
            $clean = "";
            for ($i = 0, $len = strlen($input); $i < $len; $i++) {
                $ch = $input[$i];
                if ($ch === "\x08" || ord($ch) === 127) {
                    $clean = substr($clean, 0, -1);
                    continue;
                }

                $clean .= $ch;
            }

            return $clean;
        }
    }

    /**
     * @return Arguments
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

    /**
     * @param int $seconds
     * @return void
     */
    protected function safeSleep(int $seconds = 1): void
    {
        for ($i = 0; $i < $seconds; $i++) {
            if (($i % 3) === 0) {
                $this->cli->catchPcntlSignal();
            }

            sleep(1);
        }
    }
}

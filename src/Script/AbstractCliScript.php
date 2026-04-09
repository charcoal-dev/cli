<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Script;

use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Cli\Console;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Events\State\RuntimeStatusChange;

/**
 * Abstract base class for command-line interface (CLI) scripts, providing utility methods
 * for interaction and execution in a CLI environment. Implements the basic structure and common
 * functionality required for CLI-based tasks.
 * @method void onSignalCloseCallback(int $sigId)
 */
abstract class AbstractCliScript
{
    /** @var int Set an execution time limit or 0 for infinite */
    protected const int TIME_LIMIT = 30;

    public readonly string $whoAmI;
    public readonly int $startedOn;
    public readonly int $timeLimit;
    protected(set) ExecutionState $state;

    public function __construct(public readonly Console $cli)
    {
        if (!is_int(static::TIME_LIMIT) || static::TIME_LIMIT < 0) {
            throw new \InvalidArgumentException("Invalid CLI script time limit");
        }

        $this->timeLimit = static::TIME_LIMIT;
        $this->changeState(ExecutionState::Ready);
    }

    /**
     * @param ExecutionState $state
     * @param bool $triggerEvent
     * @param \Throwable|null $eventException
     * @return void
     */
    final protected function changeState(
        ExecutionState $state,
        bool           $triggerEvent = true,
        ?\Throwable    $eventException = null
    ): void
    {
        $this->state = $state;
        if ($triggerEvent) {
            $this->cli->events->dispatch(new RuntimeStatusChange($state, exception: $eventException));
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
     * @throws \Throwable
     */
    final public function burn(): void
    {
        $this->startedOn = hrtime(true);
        if (!isset($this->whoAmI)) {
            $this->whoAmI = ObjectHelper::baseClassName($this);
        }

        try {
            $this->changeState(ExecutionState::Running);
            $this->hookBeforeExecutionStart();
            $this->exec();
        } catch (\Throwable $t) {
            $this->changeState(ExecutionState::Error);
            $this->hookAfterExecutionEnd(false);
            throw $t;
        }

        $this->changeState(ExecutionState::Finished);
        $this->hookAfterExecutionEnd(true);
    }

    /**
     * @return void
     */
    protected function hookBeforeExecutionStart(): void
    {
    }

    /**
     * @param bool $isSuccess
     * @return void
     */
    protected function hookAfterExecutionEnd(bool $isSuccess): void
    {
    }

    /**
     * @return int
     */
    final public function sinceStartedOn(): int
    {
        return hrtime(true) - $this->startedOn;
    }

    /**
     * @return void
     */
    abstract protected function exec(): void;

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
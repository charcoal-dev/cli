<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli;

use Charcoal\Base\Support\CaseStyle;
use Charcoal\Cli\Events\ConsoleEvents;
use Charcoal\Cli\Events\State\RuntimeStatus;
use Charcoal\Cli\Events\State\RuntimeStatusChange;
use Charcoal\Cli\Events\Terminate\ExceptionCaught;
use Charcoal\Cli\Events\Terminate\PcntlSignalClose;
use Charcoal\Cli\Output\AbstractOutputHandler;
use Charcoal\Cli\Script\AbstractCliScript;
use Charcoal\Cli\Script\Arguments;
use Charcoal\Cli\Script\Flags;
use Charcoal\Events\Contracts\EventStoreOwnerInterface;

/**
 * Class Console
 * @package Charcoal\Cli
 */
class Console implements EventStoreOwnerInterface
{
    protected readonly string $eventContextKey;
    public readonly ConsoleEvents $events;
    public readonly Arguments $args;
    public readonly Flags $flags;

    protected readonly ?string $argScriptName;
    protected readonly ?string $argClassname;
    protected readonly ?string $execClassname;
    protected readonly AbstractCliScript $execScriptObject;
    public readonly float $execStartedOn;

    protected array $outputBuffers = [];
    private int $exitCode = 0;

    /**
     * @param string $scriptsNamespace
     * @param array $args
     * @param string|null $defaultScriptName
     */
    public function __construct(
        protected readonly string $scriptsNamespace,
        array                     $args,
        public readonly ?string   $defaultScriptName,
    )
    {
        if (!preg_match('/^\w+(\\\\\w+)*(\\\\\*)?$/i', $this->scriptsNamespace)) {
            throw new \InvalidArgumentException('Scripts namespace contains an illegal character');
        }

        $this->eventContextKey = static::class;
        $this->events = new ConsoleEvents($this);
        $this->args = new Arguments();
        $this->flags = new Flags();

        $argCount = -1;
        foreach ($args as $arg) {
            if (!$arg) {
                continue;
            }

            $argCount++;
            if ($argCount === 0) {
                if (preg_match('/^\w+(?:\/\w+)*$/', $arg)) {
                    $this->argScriptName = $arg;
                    $this->argClassname = $this->scriptNameToClassname($arg);
                    continue;
                }

                $this->argScriptName = null;
                $this->argClassname = null;
            }

            // Set if a flag
            if (preg_match('/^-{1,2}\w+$/', $arg)) {
                switch (strtolower(ltrim($arg, "-"))) {
                    case "q":
                    case "quick":
                        $this->flags->set(Flags::QUICK);
                        break;
                    case "f":
                    case "force":
                        $this->flags->set(Flags::FORCE);
                        break;
                    case "debug":
                        $this->flags->set(Flags::DEBUG);
                        break;
                    case "v":
                    case "verbose":
                        $this->flags->set(Flags::VERBOSE);
                        break;
                    case "ansi":
                        $this->flags->set(Flags::ANSI);
                        break;
                }
            }

            // Append to arguments
            if (preg_match('/^-{0,2}\w+(=[\w@.,\-]+)?$/', $arg)) {
                $arg = explode("=", $arg);
                $this->args->set(ltrim($arg[0], "-"), $arg[1] ?? null);
                continue;
            }

            // Bad argument type
            throw new \InvalidArgumentException(
                sprintf('Unacceptable passed argument format near "%s..."', substr($arg, 0, 8))
            );
        }

        // Process control signals
        if (extension_loaded("pcntl")) {
            pcntl_signal(SIGTERM, [$this, "processControlSignalClose"]);
            pcntl_signal(SIGINT, [$this, "processControlSignalClose"]);
            pcntl_signal(SIGHUP, [$this, "processControlSignalClose"]);
            pcntl_signal(SIGQUIT, [$this, "processControlSignalClose"]);

            // For DIY implementations on SIGALRM:
            pcntl_signal(SIGALRM, [$this, "processControlSignalAlarm"]);
        }
    }

    /**
     * @param int $exitCode
     * @return void
     */
    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function processControlSignalAlarm(): void
    {
        $this->processControlSignalClose(14); // 14=SIGALRM
    }

    /**
     * @param int $sigId
     * @return void
     * @throws \Throwable
     */
    final public function processControlSignalClose(int $sigId): void
    {
        if (isset($this->execScriptObject)) {
            if (method_exists($this->execScriptObject, "onSignalCloseCallback")) {
                $this->execScriptObject->onSignalCloseCallback($sigId);
            }
        }

        $this->onSignalClose($sigId);
    }

    /**
     * @param int $sigId
     * @return never
     * @throws \Throwable
     */
    public function onSignalClose(int $sigId): never
    {
        ConsoleEvents::getEvent($this)->dispatch(new PcntlSignalClose($sigId));

        // Terminate Execution!
        exit(128 + $sigId);
    }

    /**
     * This method should be implemented in the middle of your operation flow to handle SIGTERM or other PCNTL signals.
     * Catching these signals is crucial for ensuring graceful shutdowns and proper resource management.
     * @return void
     */
    final public function catchPcntlSignal(): void
    {
        if (extension_loaded("pcntl")) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * @return void
     */
    public function onEveryLoop(): void
    {
        $this->catchPcntlSignal();
    }

    /**
     * @param \Charcoal\Cli\Output\AbstractOutputHandler $handler
     * @param string|null $identifier
     * @return $this
     */
    final public function addOutputHandler(AbstractOutputHandler $handler, ?string $identifier = null): static
    {
        $identifier = $identifier ?: $handler::class;
        $this->outputBuffers[$identifier] = $handler;
        return $this;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    final public function removeOutputHandler(string $identifier): bool
    {
        if (isset($this->outputBuffers[$identifier])) {
            unset($this->outputBuffers[$identifier]);
            return true;
        }

        return false;
    }

    /**
     * @return never
     * @throws \Throwable
     */
    final public function exec(): never
    {
        if (!$this->outputBuffers) {
            throw new \UnexpectedValueException('There are no output handlers configured');
        }

        /** @var \Charcoal\Cli\Output\AbstractOutputHandler $output */
        foreach ($this->outputBuffers as $output) {
            $output->startBuffer($this);
        }

        // Exec success signal
        $this->execStartedOn = microtime(true);
        $execSuccess = false;

        try {
            // Preparing to execute script
            ConsoleEvents::getEvent($this)->dispatch(new RuntimeStatusChange(RuntimeStatus::Prepare));

            // Load script
            try {
                $scriptClassname = null;
                if (isset($this->argClassname)) {
                    if (!class_exists($this->argClassname)) {
                        throw new \RuntimeException(sprintf('Script class for "%s" does not exist', $this->argScriptName));
                    }

                    $scriptClassname = $this->argClassname;
                }

                if (!$scriptClassname && $this->defaultScriptName) {
                    $defaultScriptClassname = $this->scriptNameToClassname($this->defaultScriptName);
                    if (!class_exists($defaultScriptClassname)) {
                        throw new \RuntimeException(sprintf('Default script class "%s" does not exist', $this->defaultScriptName));
                    }

                    $scriptClassname = $defaultScriptClassname;
                }

                if (!$scriptClassname) {
                    throw new \RuntimeException("No script specified to execute!");
                }

                if (!is_a($scriptClassname, AbstractCliScript::class, true)) {
                    throw new \RuntimeException(
                        sprintf('Script class for "%s" must extend "%s" class', $scriptClassname, AbstractCliScript::class)
                    );
                }

                $this->execClassname = $scriptClassname;
                $this->execScriptObject = new $scriptClassname($this);
            } catch (\RuntimeException $e) {
                ConsoleEvents::getEvent($this)->dispatch(new RuntimeStatusChange(
                    RuntimeStatus::ScriptNotFound,
                    $scriptClassname
                ));

                throw $e;
            }

            // Set time limit
            if ($this->execScriptObject->timeLimit > 0) {
                if (extension_loaded("pcntl")) {
                    pcntl_alarm($this->execScriptObject->timeLimit);
                }

                set_time_limit($this->execScriptObject->timeLimit);
            }

            // Script is loaded trigger
            ConsoleEvents::getEvent($this)->dispatch(new RuntimeStatusChange(RuntimeStatus::Ready));

            // Execute script
            try {
                $this->execScriptObject->exec();
                $execSuccess = true;
            } catch (\Throwable $t) {
                ConsoleEvents::getEvent($this)->dispatch(new ExceptionCaught($t));
                throw $t;
            }
        } catch (\Throwable $t) {
            $this->exception2Str($t);
        }

        // Execution
        $this->print("");
        if ($execSuccess) {
            $this->print("{green}Execution finished!{/}");
        } else {
            $this->print("{red}Execution finished with an exception!{/}");
        }

        // After script exec event

        ConsoleEvents::getEvent($this)->dispatch(new RuntimeStatusChange(RuntimeStatus::Completed,
            isSuccess: $execSuccess));

        $this->print("");
        $this->print(sprintf("Execution time: {grey}%ss{/}", number_format(microtime(true) - $this->execStartedOn, 4)));
        $this->printMemoryConsumption();

        /** @var \Charcoal\Cli\Output\AbstractOutputHandler $output */
        foreach ($this->outputBuffers as $output) {
            $output->endBuffer();
        }

        exit($this->exitCode);
    }

    /**
     * @param string $arg
     * @return string
     */
    private function scriptNameToClassname(string $arg): string
    {
        return $this->scriptsNamespace . "\\" . implode("\\", array_map(function ($part) {
                return CaseStyle::PASCAL_CASE->from($part);
            }, explode("/", $arg)));
    }

    /**
     * @return void
     */
    final public function printMemoryConsumption(): void
    {
        $memoryUsage = number_format((memory_get_usage(false) / 1024) / 1024, 2);
        $memoryUsageReal = number_format((memory_get_usage(true) / 1024) / 1024, 2);
        $this->print(sprintf("Memory usage: {grey}%sMB{/} / {grey}%sMB{/}", $memoryUsage, $memoryUsageReal));

        $peakMemoryUsage = number_format((memory_get_peak_usage(false) / 1024) / 1024, 2);
        $peakMemoryUsageReal = number_format((memory_get_peak_usage(true) / 1024) / 1024, 2);
        $this->print(sprintf("Peak Memory usage: {grey}%sMB{/} / {grey}%sMB{/}", $peakMemoryUsage, $peakMemoryUsageReal));
    }

    /**
     * @param string $line
     * @param int $interval
     * @param bool $eol
     * @return void
     */
    final public function typewriter(string $line, int $interval = 100, bool $eol = false): void
    {
        if ($interval <= 0) {
            throw new \InvalidArgumentException('Typewrite method requires positive interval');
        }

        $quickFlag = $this->flags->isQuick();
        $chars = str_split($line);
        foreach ($chars as $char) {
            $this->inline($char);
            if (!$quickFlag) {
                $this->microSleep($interval);
            }
        }

        if ($eol) {
            $this->print("{/}");
        }
    }

    /**
     * @param string $char
     * @param int $count
     * @param int $interval
     * @param bool $eol
     * @return void
     */
    final public function repeatChar(string $char = ".", int $count = 10, int $interval = 100, bool $eol = false): void
    {
        if ($interval <= 0) {
            throw new \InvalidArgumentException('Repeat method requires positive interval');
        }

        $this->typewriter(str_repeat($char, $count), $interval, $eol);
    }

    /**
     * @param string $data
     * @param bool $eol
     * @return void
     */
    private function writeToOutputHandlers(string $data, bool $eol): void
    {
        /** @var \Charcoal\Cli\Output\AbstractOutputHandler $output */
        foreach ($this->outputBuffers as $output) {
            $output->write($data, $eol);
        }
    }

    /**
     * @return void
     */
    final public function eol(): void
    {
        $this->writeToOutputHandlers("", true);
    }

    /**
     * @param string $line
     * @param int $sleep
     */
    final public function print(string $line, int $sleep = 0): void
    {
        $this->writeToOutputHandlers($line, true);
        if (!$this->flags->isQuick()) {
            $this->microSleep($sleep);
        }
    }

    /**
     * @param string $line
     * @param int $sleep
     */
    final public function inline(string $line, int $sleep = 0): void
    {
        $this->writeToOutputHandlers($line, false);
        if (!$this->flags->isQuick()) {
            $this->microSleep($sleep);
        }
    }

    /**
     * @param int $milliseconds
     */
    final public function microSleep(int $milliseconds = 0): void
    {
        if ($milliseconds > 0) {
            usleep(intval(($milliseconds / 1000) * pow(10, 6)));
        }
    }

    /**
     * @param \Throwable $t
     * @param int $tabIndex
     */
    final public function exception2Str(\Throwable $t, int $tabIndex = 0): void
    {
        $tabs = str_repeat("\t", $tabIndex);
        $this->print("");
        $this->repeatChar(".", 10, 50, true);
        $this->print("");
        $this->print($tabs . sprintf('{yellow}Caught:{/} {red}{b}%s{/}', get_class($t)));
        $this->print($tabs . sprintf("{yellow}Message:{/} {cyan}%s{/}", $t->getMessage()));
        $this->print($tabs . sprintf("{yellow}File:{/} %s", $t->getFile()));
        $this->print($tabs . sprintf("{yellow}Line:{/} {cyan}%d{/}", $t->getLine()));
        $this->print($tabs . "{yellow}Debug Backtrace:");
        $this->print($tabs . "┬");

        foreach ($t->getTrace() as $trace) {
            $function = $trace["function"] ?? null;
            $class = $trace["class"] ?? null;
            $type = $trace["type"] ?? null;
            $file = $trace["file"] ?? null;
            $line = $trace["line"] ?? null;

            if ($file && is_string($file) && $line) {
                $method = $function;
                if ($class && $type) {
                    $method = $class . $type . $function;
                }

                $traceString = sprintf('"{u}{cyan}%s{/}" on line # {u}{yellow}%d{/}', $file, $line);
                if ($method) {
                    $traceString = sprintf('Method {u}{magenta}%s(){/} in file ', $method) . $traceString;
                }

                $this->print($tabs . "├─ " . $traceString);
            }

            unset($trace, $traceString, $function, $class, $type, $file, $line);
        }

        if ($t->getPrevious()) {
            $this->print($tabs);
            $this->print($tabs . "{red}Caused By:{/}");
            $this->exception2Str($t->getPrevious(), $tabIndex + 1);
        }
    }

    /**
     * @return string
     */
    public function eventsUniqueContextKey(): string
    {
        return $this->eventContextKey;
    }
}

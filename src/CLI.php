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

use Charcoal\CLI\Console\AbstractOutputHandler;
use Charcoal\Filesystem\Directory;
use Charcoal\OOP\CaseStyles;
use Charcoal\OOP\OOP;

/**
 * Class CLI
 * @package Charcoal\CLI
 */
class CLI
{
    public readonly Events $events;
    public readonly Arguments $args;
    public readonly float $execStartStamp;
    public readonly Flags $flags;

    protected array $outputs = [];
    protected ?string $execClassname = null;
    protected ?string $scriptName = null;
    protected ?AbstractCliScript $execScriptObject = null;

    /**
     * @param \Charcoal\Filesystem\Directory $dir
     * @param array $args
     */
    public function __construct(private readonly Directory $dir, array $args)
    {
        $this->events = new Events();
        $this->args = new Arguments();
        $this->flags = new Flags();

        $argCount = -1;
        foreach ($args as $arg) {
            if (!$arg) {
                continue;
            }

            $argCount++;
            if ($argCount === 0) {
                if (preg_match('/^\w+$/', $arg)) {
                    $this->scriptName = $arg;
                    continue;
                }
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
            if (preg_match('/^-{0,2}\w+(=[\w@.\-]+)?$/', $arg)) {
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

            // For DIY implementations on SIGLARM:
            pcntl_signal(SIGALRM, [$this, "processControlSignalAlarm"]);
        }
    }

    /**
     * @param int $sigId
     * @return never
     * @noinspection PhpUnusedParameterInspection
     */
    public function onSignalClose(int $sigId): never
    {
        exit;
    }

    /**
     * @return void
     */
    public function processControlSignalAlarm(): void
    {
        $this->processControlSignalClose(14); // 14=SIGALRM
    }

    /**
     * @param int $sigId
     * @return void
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
     * @return void
     */
    public function onEveryLoop(): void
    {
        if (extension_loaded("pcntl")) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * @param \Charcoal\CLI\Console\AbstractOutputHandler $handler
     * @return $this
     */
    final public function addOutputHandler(AbstractOutputHandler $handler): static
    {
        $this->outputs[] = $handler;
        return $this;
    }

    /**
     * Alias of "exec" method
     * @return never
     * @throws \Throwable
     */
    final public function burn(): never
    {
        $this->exec();
    }

    /**
     * @return never
     * @throws \Throwable
     */
    final public function exec(): never
    {
        if (!$this->outputs) {
            throw new \UnexpectedValueException('There are no output handlers configured');
        }

        /** @var \Charcoal\CLI\Console\AbstractOutputHandler $output */
        foreach ($this->outputs as $output) {
            $output->startBuffer($this);
        }

        // Exec success signal
        $this->execStartStamp = microtime(true);
        $execSuccess = false;

        try {
            // Scripts namespace autoloader
            $scriptsDirectoryPath = $this->dir->path;
            spl_autoload_register(function (string $class) use ($scriptsDirectoryPath) {
                if (preg_match('/^scripts\\\\\w+$/', $class)) {
                    $className = OOP::baseClassName($class);
                    $classFilename = CaseStyles::snake_case($className);
                    $classFilepath = $scriptsDirectoryPath . DIRECTORY_SEPARATOR . $classFilename . ".php";
                    if (@is_file($classFilepath)) {
                        @include_once($classFilepath);
                    }
                }
            });

            // Before execution starts
            $this->events->beforeExec()->trigger([$this]);

            // Load script
            try {
                $scriptName = $this->scriptName ?? "default";
                $scriptClassname = "scripts\\" . CaseStyles::snake_case($scriptName);
                if (!class_exists($scriptClassname)) {
                    throw new \RuntimeException(sprintf('Script class "%s" does not exist', $scriptClassname));
                } elseif (!is_a($scriptClassname, AbstractCliScript::class, true)) {
                    throw new \RuntimeException(
                        sprintf('Script class "%s" must extend "%s" class', $scriptClassname, AbstractCliScript::class)
                    );
                }

                $this->execClassname = $scriptClassname;
                $this->execScriptObject = new $scriptClassname($this);
            } catch (\RuntimeException $e) {
                $this->events->scriptNotFound()->trigger([$this, $scriptClassname ?? ""]);
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
            $this->events->scriptLoaded()->trigger([$this, $this->execScriptObject]);

            // Execute script
            try {
                $this->execScriptObject->exec();
                $execSuccess = true;
            } catch (\Throwable $t) {
                $this->events->scriptExecException()->trigger([$this, $this->execScriptObject, $t]);
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
        $this->events->afterExec()->trigger([$this, $execSuccess, $this->execScriptObject]);
        $this->print("");
        $this->print(sprintf("Execution time: {grey}%ss{/}", number_format(microtime(true) - $this->execStartStamp, 4)));
        $this->printMemoryConsumption();

        /** @var \Charcoal\CLI\Console\AbstractOutputHandler $output */
        foreach ($this->outputs as $output) {
            $output->endBuffer($this);
        }

        exit();
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
        /** @var \Charcoal\CLI\Console\AbstractOutputHandler $output */
        foreach ($this->outputs as $output) {
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
}

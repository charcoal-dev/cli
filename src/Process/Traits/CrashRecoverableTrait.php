<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process\Traits;

use Charcoal\Cli\Process\ProcessRecoveryPolicy;

/**
 * Provides functionalities for a class to recover after a crash, using a defined recovery process policy.
 */
trait CrashRecoverableTrait
{
    protected readonly ?ProcessRecoveryPolicy $recovery;

    abstract protected function declareRecoverableProcess(): ?ProcessRecoveryPolicy;

    /**
     * @return void
     */
    public function recoveryOnConstructHook(): void
    {
        $this->recovery = $this->declareRecoverableProcess();
    }

    /**
     * @return bool
     */
    public function isRecoverable(): bool
    {
        if (isset($this->recovery)) {
            if ($this->recovery->recoverable &&
                $this->recovery->ticks > 0 &&
                $this->recovery->ticksInterval > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    protected function beforeHealingStart(): void
    {
        $this->memoryCleanup();
    }

    protected function onHealingFinished(): void
    {
        $this->cli->catchPcntlSignal();

        $this->print("{cyan}~~~~~");
        $this->print("{cyan}Restating App Process...");
//        if ($this->logger) {
//            $this->logger->context->log("App Process Restarted");
//            $this->logger->changeState(CliScriptState::STARTED);
//            $this->logger->saveStateContext()->captureCpuStats(upsertState: false);
//        }

        $this->print("");
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    final protected function handleRecoveryAfterCrash(): void
    {
        $this->print("");
        $this->inline(sprintf("{grey}Recovery in {b}%d{/} ticks ", $this->recovery->ticks));
        $recoveryEta = round(($this->recovery->ticks * $this->recovery->ticksInterval) / $this->recovery->ticksInterval, 1);
//        if ($this->logger) {
//            $this->logger->context->log(sprintf("Recovery expected in %s seconds", $recoveryEta));
//            $this->logger->changeState(CliScriptState::HEALING);
//            $this->logger->saveStateContext()->captureCpuStats(upsertState: false);
//        }

        $this->beforeHealingStart();

        for ($i = 0; $i < $this->recovery->ticks; $i++) {
            if (($i % 3) === 0) { // On every 3rd tick
                $this->cli->catchPcntlSignal();
            }

            usleep($this->recovery->ticksInterval);
            $this->inline(".");
        }

        $this->print("")->print("");
        $this->onHealingFinished();
    }
}
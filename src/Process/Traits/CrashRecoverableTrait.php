<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Process\Traits;

use Charcoal\Cli\Enums\ExecutionState;
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
    public function onStartRecovery(): void
    {
        if ($this->state !== ExecutionState::Healing) {
            throw new \BadMethodCallException("Recovery process can only be started in HEALING state");
        }

        $this->memoryCleanup();
    }

    /**
     * @return void
     */
    public function onEndRecovery(): void
    {
        if ($this->state !== ExecutionState::Healing) {
            throw new \BadMethodCallException("Recovery process not in HEALING state");
        }
    }
}
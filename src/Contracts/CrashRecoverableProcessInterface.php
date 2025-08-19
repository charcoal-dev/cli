<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Contracts;

/**
 * This interface defines methods for processes that can recover from crashes.
 * Implementations of this interface should determine if the process is recoverable,
 * execute any necessary recovery logic upon construction, and handle recovery
 * actions post-crash.
 */
interface CrashRecoverableProcessInterface
{
    public function isRecoverable(): bool;

    public function recoveryOnConstructHook(): void;

    public function handleRecoveryAfterCrash(): void;
}
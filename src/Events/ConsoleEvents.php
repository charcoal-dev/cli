<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Events;

use Charcoal\Cli\Console;
use Charcoal\Cli\Events\State\RuntimeStatusChange;
use Charcoal\Cli\Events\Terminate\ExceptionCaught;
use Charcoal\Cli\Events\Terminate\PcntlSignalClose;
use Charcoal\Events\BehaviorEvent;
use Charcoal\Events\Dispatch\DispatchReport;
use Charcoal\Events\Subscriptions\Subscription;
use Charcoal\Events\Support\Traits\EventStaticScopeTrait;

/**
 * Class ConsoleEvents
 * @package Charcoal\Cli\Events
 * @template T of ConsoleEvents
 * @template S of Console
 * @template E of RuntimeStatusChange|ExceptionCaught|PcntlSignalClose
 */
class ConsoleEvents extends BehaviorEvent
{
    use EventStaticScopeTrait;

    /**
     * @param Console $cli
     */
    public function __construct(protected readonly Console $cli)
    {
        parent::__construct("consoleExecutionState", [
            ConsoleEventSignal::class,
            RuntimeStatusChange::class,
            ExceptionCaught::class,
            PcntlSignalClose::class,
        ]);

        $this->registerStaticEventStore($this->cli);
    }

    /**
     * @return Subscription
     */
    public function subscribe(): Subscription
    {
        return $this->createSubscription("cli-script-" . count($this->subscribers()) .
            "-" . substr(uniqid(), 0, 4));
    }

    /**
     * @param E $context
     * @return DispatchReport
     */
    public function dispatch(ConsoleEventSignal $context): DispatchReport
    {
        return $this->dispatchEvent($context);
    }
}
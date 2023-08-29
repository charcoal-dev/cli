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

use Charcoal\Events\Event;
use Charcoal\Events\EventsRegistry;

/**
 * Class Events
 * @package Charcoal\CLI
 */
class Events
{
    private readonly EventsRegistry $registry;

    public function __construct()
    {
        $this->registry = new EventsRegistry();
    }

    /**
     * Callback first argument is instance of CLI obj
     * @return Event
     */
    public function beforeExec(): Event
    {
        return $this->registry->on("before_exec");
    }

    /**
     * Callback first argument is instance of CLI obj
     * Callback second argument is boolean, if script exec method finishes without any thrown exceptions, its value is TRUE otherwise FALSE
     * @return Event
     */
    public function afterExec(): Event
    {
        return $this->registry->on("after_exec");
    }

    /**
     * Callback first argument is instance of CLI obj
     * Callback second argument is string class name
     * @return Event
     */
    public function scriptNotFound(): Event
    {
        return $this->registry->on("script_not_found");
    }

    /**
     * Callback first argument is instance of CLI obj
     * Callback second argument is instance of Abstract_CLI_Script
     * @return Event
     */
    public function scriptLoaded(): Event
    {
        return $this->registry->on("script_loaded");
    }

    /**
     * Callback first argument is instance of CLI obj
     * Callback second argument will be an instance of \Throwable
     * @return Event
     */
    public function scriptExecException(): Event
    {
        return $this->registry->on("script_exec_exception");
    }
}


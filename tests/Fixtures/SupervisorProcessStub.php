<?php
/**
 * Part of the "charcoal-dev/cli" package.
 * @link https://github.com/charcoal-dev/cli
 */

declare(strict_types=1);

namespace Charcoal\Cli\Tests\Fixtures {

    use Charcoal\Cli\Contracts\Supervisor\SupervisorInterface;
    use Charcoal\Cli\Process\Supervisor\SupervisorConfig;
    use Charcoal\Cli\Process\Traits\SupervisorProcessTrait;

    final class SupervisorProcessStub implements SupervisorInterface
    {
        use SupervisorProcessTrait;

        public static bool $pcntlAvailable = true;
        public static int $forkResult = 12345;
        public static int $forkCalls = 0;
        public bool $prepared = false;

        public function __construct()
        {
            $this->supervisorOnConstructHook();
        }

        protected function declareSupervisorConfig(): SupervisorConfig
        {
            return new SupervisorConfig(maxChildren: 1);
        }

        protected function prepareChildProcess(): void
        {
            $this->prepared = true;
        }
    }
}

// Loaded only inside isolated test processes so these doubles never affect other tests.
namespace Charcoal\Cli\Process\Traits {

    use Charcoal\Cli\Tests\Fixtures\SupervisorProcessStub;

    function extension_loaded(string $extension): bool
    {
        return $extension === "pcntl" ? SupervisorProcessStub::$pcntlAvailable : \extension_loaded($extension);
    }

    function pcntl_fork(): int
    {
        SupervisorProcessStub::$forkCalls++;
        return SupervisorProcessStub::$forkResult;
    }
}

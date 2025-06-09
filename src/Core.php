<?php

namespace Laravel\Nightwatch;

use Laravel\Nightwatch\Contracts\LocalIngest;
use Laravel\Nightwatch\State\CommandState;
use Laravel\Nightwatch\State\RequestState;
use Psr\Log\LoggerInterface;
use Throwable;

use function call_user_func;

/**
 * @template TState of RequestState|CommandState
 */
final class Core
{
    /**
     * @param  TState  $state
     * @param  (callable(): LoggerInterface)  $emergencyLoggerResolver
     */
    public function __construct(
        public LocalIngest $ingest,
        public SensorManager $sensor,
        public RequestState|CommandState $state,
        public Clock $clock,
        public bool $enabled,
        private $emergencyLoggerResolver,
    ) {
        //
    }

    public function report(Throwable $e): void
    {
        if (! $this->enabled) {
            return;
        }

        try {
            $this->sensor->exception($e);
        } catch (Throwable $e) {
            $this->handleUnrecoverableException($e);
        }
    }

    /**
     * @internal
     */
    public function ingest(): void
    {
        try {
            $this->ingest->write($this->state->records->flush());
        } catch (Throwable $e) {
            $this->handleUnrecoverableException($e);
        }
    }

    /**
     * @internal
     *
     * @return Core<TState>
     */
    public function setSensor(SensorManager $sensor): self
    {
        $this->sensor = $sensor;

        return $this;
    }

    /**
     * @internal
     */
    public function handleUnrecoverableException(Throwable $e): void
    {
        try {
            $logger = call_user_func($this->emergencyLoggerResolver);

            $logger->critical('[nightwatch] '.$e->getMessage(), [
                'exception' => $e,
            ]);
        } catch (Throwable $e) {
            //
        }
    }
}

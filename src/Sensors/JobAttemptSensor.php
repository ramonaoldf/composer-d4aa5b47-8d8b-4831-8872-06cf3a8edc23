<?php

namespace Laravel\Nightwatch\Sensors;

use Illuminate\Queue\Events\JobAttempted;
use Laravel\Nightwatch\Clock;
use Laravel\Nightwatch\Concerns\NormalizesQueue;
use Laravel\Nightwatch\Records\JobAttempt;
use Laravel\Nightwatch\State\CommandState;

use function hash;
use function round;

/**
 * @internal
 */
final class JobAttemptSensor
{
    use NormalizesQueue;

    /**
     * @param  array<string, array{ queue?: string, driver?: string, prefix?: string, suffix?: string }>  $connectionConfig
     */
    public function __construct(
        private CommandState $executionState,
        private Clock $clock,
        private array $connectionConfig,
    ) {
        //
    }

    public function __invoke(JobAttempted $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        $now = $this->clock->microtime();
        $name = $event->job->resolveName();

        $this->executionState->records->write(new JobAttempt(
            timestamp: $this->executionState->timestamp,
            deploy: $this->executionState->deploy,
            server: $this->executionState->server,
            _group: hash('xxh128', $name),
            trace_id: $this->executionState->trace,
            user: $this->executionState->user->id(),
            job_id: $event->job->uuid(), // @phpstan-ignore argument.type
            attempt_id: $this->executionState->id(),
            attempt: $event->job->attempts(),
            name: $name,
            connection: $event->job->getConnectionName(),
            queue: $this->normalizeQueue($event->job->getConnectionName(), $event->job->getQueue()),
            status: match (true) {
                $event->job->isReleased() => 'released',
                $event->job->hasFailed() => 'failed',
                default => 'processed',
            },
            duration: (int) round(($now - $this->executionState->timestamp) * 1_000_000),
            exceptions: $this->executionState->exceptions,
            logs: $this->executionState->logs,
            queries: $this->executionState->queries,
            lazy_loads: $this->executionState->lazyLoads,
            jobs_queued: $this->executionState->jobsQueued,
            mail: $this->executionState->mail,
            notifications: $this->executionState->notifications,
            outgoing_requests: $this->executionState->outgoingRequests,
            files_read: $this->executionState->filesRead,
            files_written: $this->executionState->filesWritten,
            cache_events: $this->executionState->cacheEvents,
            hydrated_models: $this->executionState->hydratedModels,
            peak_memory_usage: $this->executionState->peakMemory(),
        ));
    }
}

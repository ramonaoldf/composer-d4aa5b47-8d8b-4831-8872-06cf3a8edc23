<?php

namespace Laravel\Nightwatch\Sensors;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Laravel\Nightwatch\Clock;
use Laravel\Nightwatch\Concerns\NormalizesQueue;
use Laravel\Nightwatch\Contracts\Ingest;
use Laravel\Nightwatch\LazyValue;
use Laravel\Nightwatch\Records\JobAttempt;
use Laravel\Nightwatch\State\CommandState;
use Laravel\Nightwatch\Types\Str;

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
        private Ingest $ingest,
        private CommandState $commandState,
        private Clock $clock,
        private array $connectionConfig,
    ) {
        //
    }

    public function __invoke(JobProcessed|JobReleasedAfterException|JobFailed $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        $now = $this->clock->microtime();
        $name = $event->job->resolveName();

        $this->ingest->write(new JobAttempt(
            timestamp: $this->commandState->timestamp,
            deploy: $this->commandState->deploy,
            server: $this->commandState->server,
            _group: hash('xxh128', $name),
            trace_id: $this->commandState->trace,
            user: $this->commandState->user->id(),
            job_id: $event->job->uuid(), // @phpstan-ignore argument.type
            attempt_id: $this->commandState->id(),
            attempt: $event->job->attempts(),
            name: $name,
            connection: $event->job->getConnectionName(),
            queue: $this->normalizeQueue($event->job->getConnectionName(), $event->job->getQueue()),
            status: match (true) {
                $event->job->isReleased() => 'released',
                $event->job->hasFailed() => 'failed',
                default => 'processed',
            },
            duration: (int) round(($now - $this->commandState->timestamp) * 1_000_000),
            exceptions: new LazyValue(fn () => $this->commandState->exceptions),
            logs: new LazyValue(fn () => $this->commandState->logs),
            queries: new LazyValue(fn () => $this->commandState->queries),
            lazy_loads: new LazyValue(fn () => $this->commandState->lazyLoads),
            jobs_queued: new LazyValue(fn () => $this->commandState->jobsQueued),
            mail: new LazyValue(fn () => $this->commandState->mail),
            notifications: new LazyValue(fn () => $this->commandState->notifications),
            outgoing_requests: new LazyValue(fn () => $this->commandState->outgoingRequests),
            files_read: new LazyValue(fn () => $this->commandState->filesRead),
            files_written: new LazyValue(fn () => $this->commandState->filesWritten),
            cache_events: new LazyValue(fn () => $this->commandState->cacheEvents),
            hydrated_models: new LazyValue(fn () => $this->commandState->hydratedModels),
            peak_memory_usage: new LazyValue(fn () => $this->commandState->peakMemory()),
            exception_preview: new LazyValue(fn () => Str::tinyText($this->commandState->exceptionPreview)),
        ));
    }
}

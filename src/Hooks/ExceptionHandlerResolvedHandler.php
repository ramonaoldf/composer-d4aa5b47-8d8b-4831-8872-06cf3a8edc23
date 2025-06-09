<?php

namespace Laravel\Nightwatch\Hooks;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\State\CommandState;
use Laravel\Nightwatch\State\RequestState;
use Throwable;

/**
 * @internal
 */
final class ExceptionHandlerResolvedHandler
{
    /**
     * @param  Core<RequestState|CommandState>  $nightwatch
     */
    public function __construct(
        private Core $nightwatch,
    ) {
        //
    }

    public function __invoke(ExceptionHandler $handler): void
    {
        try {
            if ($handler instanceof Handler) {
                /**
                 * @see \Laravel\Nightwatch\Records\Exception
                 *
                 * TODO ensure this isn't a memory leak in Octane
                 */
                $handler->reportable(new ReportableHandler($this->nightwatch));
            }
        } catch (Throwable $e) {
            $this->nightwatch->report($e);
        }
    }
}

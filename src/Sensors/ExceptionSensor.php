<?php

namespace Laravel\Nightwatch\Sensors;

use Illuminate\View\ViewException;
use Laravel\Nightwatch\Clock;
use Laravel\Nightwatch\Location;
use Laravel\Nightwatch\Records\Exception;
use Laravel\Nightwatch\State\CommandState;
use Laravel\Nightwatch\State\RequestState;
use Spatie\LaravelIgnition\Exceptions\ViewException as IgnitionViewException;
use Throwable;

use function array_is_list;
use function array_keys;
use function array_map;
use function count;
use function debug_backtrace;
use function gettype;
use function hash;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function json_encode;

/**
 * @internal
 */
final class ExceptionSensor
{
    public function __construct(
        private Clock $clock,
        private RequestState|CommandState $executionState,
        private Location $location,
    ) {
        //
    }

    public function __invoke(Throwable $e): void
    {
        $nowMicrotime = $this->clock->microtime();
        [$file, $line] = $this->location->forException($e);
        $normalizedException = match ($e->getPrevious()) {
            null => $e,
            default => match (true) {
                $e instanceof ViewException,
                $e instanceof IgnitionViewException => $e->getPrevious(),
                default => $e,
            },
        };

        $this->executionState->exceptions++;

        $this->executionState->records->write(new Exception(
            timestamp: $nowMicrotime,
            deploy: $this->executionState->deploy,
            server: $this->executionState->server,
            _group: hash('md5', $normalizedException::class.','.$normalizedException->getCode().','.$file.','.$line),
            trace_id: $this->executionState->trace,
            execution_source: $this->executionState->source,
            execution_id: $this->executionState->id(),
            execution_stage: $this->executionState->stage,
            user: $this->executionState->user->id(),
            class: $normalizedException::class,
            file: $file,
            line: $line ?? 0,
            message: $normalizedException->getMessage(),
            code: (string) $normalizedException->getCode(),
            trace: $this->serializeTrace($normalizedException),
            handled: $this->wasManuallyReported($normalizedException),
            php_version: $this->executionState->phpVersion,
            laravel_version: $this->executionState->laravelVersion,
        ));
    }

    private function wasManuallyReported(Throwable $e): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 20) as $frame) {
            if ($frame['function'] === 'report' && ! isset($frame['type'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see https://github.com/php/php-src/blob/f17c2203883ddf53adfcb33d85523d11429729ab/Zend/zend_exceptions.c
     */
    private function serializeTrace(Throwable $e): string
    {
        $trace = [];

        foreach ($e->getTrace() as $frame) {
            $file = match (true) {
                ! isset($frame['file']) => '[internal function]',
                ! is_string($frame['file']) => '[unknown file]', // @phpstan-ignore booleanNot.alwaysFalse
                default => $this->location->normalizeFile($frame['file']),
            };

            if (isset($frame['line']) && is_int($frame['line'])) { // @phpstan-ignore booleanAnd.rightAlwaysTrue
                $file .= ':'.$frame['line'];
            }

            $source = '';

            if (isset($frame['class']) && is_string($frame['class'])) { // @phpstan-ignore booleanAnd.rightAlwaysTrue
                $source .= $frame['class'];
            }

            if (isset($frame['type']) && is_string($frame['type'])) { // @phpstan-ignore booleanAnd.rightAlwaysTrue
                $source .= $frame['type'];
            }

            if (isset($frame['function']) && is_string($frame['function'])) { // @phpstan-ignore booleanAnd.rightAlwaysTrue, isset.offset
                $source .= $frame['function'];
            }

            $source .= '(';

            if (isset($frame['args']) && is_array($frame['args']) && count($frame['args']) > 0) { // @phpstan-ignore booleanAnd.rightAlwaysTrue
                $args = array_map(static fn ($argument) => match (gettype($argument)) {
                    'NULL' => 'null',
                    'boolean' => 'bool',
                    'integer' => 'int',
                    'double' => 'float',
                    'array' => 'array',
                    'object' => $argument::class,
                    'resource' => 'resource',
                    'resource (closed)' => 'resource (closed)',
                    'string' => 'string',
                    'unknown type' => '[unknown]',
                }, $frame['args']);

                if (! array_is_list($args)) {
                    $args = array_map(static fn ($value, $key) => "{$key}: {$value}", $args, array_keys($args));
                }

                $source .= implode(', ', $args);
            }

            $source .= ')';

            $trace[] = ['file' => $file, 'source' => $source];
        }

        return json_encode($trace, flags: JSON_THROW_ON_ERROR);
    }
}

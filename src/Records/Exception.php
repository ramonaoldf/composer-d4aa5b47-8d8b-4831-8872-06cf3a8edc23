<?php

namespace Laravel\Nightwatch\Records;

use Laravel\Nightwatch\ExecutionStage;
use Laravel\Nightwatch\LazyValue;
use Laravel\Nightwatch\Types\Str;

/**
 * @internal
 */
final class Exception extends Record
{
    public int $v = 1;

    public string $t = 'exception';

    /**
     * @param  string|LazyValue<string>  $trace_id
     * @param  LazyValue<string>  $execution_id
     * @param  LazyValue<string>  $execution_preview
     * @param  string|LazyValue<string>  $user
     */
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $_group,
        public string|LazyValue $trace_id,
        public string $execution_source,
        public LazyValue $execution_id,
        public LazyValue $execution_preview,
        public ExecutionStage $execution_stage,
        public string|LazyValue $user,
        // --- //
        public string $class,
        public string $file,
        public int $line,
        public string $message,
        public string $code,
        public string $trace,
        public bool $handled,
        public string $php_version,
        public string $laravel_version,
    ) {
        $this->class = Str::tinyText($this->class);
        $this->message = Str::text($this->message);
        $this->file = Str::tinyText($this->file);
        $this->trace = Str::mediumText($this->trace);
    }
}

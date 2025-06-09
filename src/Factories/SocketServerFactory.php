<?php

namespace Laravel\Nightwatch\Factories;

use Illuminate\Contracts\Foundation\Application;
use React\Socket\ServerInterface;
use React\Socket\TcpServer;

/**
 * @internal
 */
final class SocketServerFactory
{
    /**
     * @param  array{
     *      enabled?: bool,
     *      token?: string,
     *      auth_url?: string,
     *      deployment?: string,
     *      server?: string,
     *      local_ingest?: string,
     *      remote_ingest?: string,
     *      buffer_threshold?: int,
     *      error_log_channel?: string,
     *      ingests: array{
     *          socket?: array{ uri?: string, connection_timeout?: float, timeout?: float },
     *          http?: array{ connection_timeout?: float, timeout?: float },
     *          log?: array{ channel?: string },
     *      }
     * }  $config
     */
    public function __construct(
        private array $config,
    ) {
        //
    }

    public function __invoke(Application $app): ServerInterface
    {
        return new TcpServer($this->config['ingests']['socket']['uri'] ?? '');
    }
}

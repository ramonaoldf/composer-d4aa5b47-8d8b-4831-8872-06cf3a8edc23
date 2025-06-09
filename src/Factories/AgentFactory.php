<?php

namespace Laravel\Nightwatch\Factories;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Env;
use Laravel\Nightwatch\Buffers\StreamBuffer;
use Laravel\Nightwatch\Console\Agent;

/**
 * @internal
 */
final class AgentFactory
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

    public function __invoke(Application $app): Agent
    {
        $debug = (bool) Env::get('NIGHTWATCH_DEBUG');

        $ingestDetails = (new IngestDetailsRepositoryFactory($this->config))($app);

        // Creating an instance of the `TcpServer` will automatically start the
        // server. To ensure we do not start the server when the command is
        // constructed, which will happen when running the `php artisan list`
        // command, we make sure to resolve the server only when actually
        // running the command.
        $app->bindMethod([Agent::class, 'handle'], fn (Agent $agent, Application $app) => $agent->handle(
            (new SocketServerFactory($this->config))($app),
            (new RemoteIngestFactory($this->config, $ingestDetails, $debug))($app),
            $ingestDetails,
        ));

        return new Agent(new StreamBuffer, $debug ? 1 : 10);
    }
}

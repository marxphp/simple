<?php

declare(strict_types=1);

/**
 * This file is part of MarxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace App\Console\Command\Server;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Loop;
use Amp\Socket\Server;
use App\Http\Kernel;
use App\Http\ServerRequest;
use App\Logger;
use Max\Di\Context;
use Max\Event\EventDispatcher;
use Max\Http\Server\Event\OnRequest;
use Max\Http\Server\ResponseEmitter\AmpResponseEmitter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AmpServerCommand extends BaseServerCommand
{
    protected string $container = 'AmPHP';

    protected function configure()
    {
        $this->setName('serve:amp')
            ->setDescription('Start AmPHP server');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists('Amp\Http\Server\HttpServer')) {
            throw new \RuntimeException('You should install the amphp/http-server package before starting.');
        }

        $container       = Context::getContainer();
        $kernel          = $container->make(Kernel::class);
        $logger          = $container->make(Logger::class)->get();
        $eventDispatcher = $container->make(EventDispatcher::class);
        Loop::run(function () use ($kernel, $logger, $eventDispatcher) {
            $sockets = [
                Server::listen("{$this->host}:{$this->port}"),
                //                    Server::listen("[::]:{$port}"),
            ];

            $server = new HttpServer($sockets, new CallableRequestHandler(function (Request $request) use ($kernel, $eventDispatcher) {
                $serverRequest = ServerRequest::createFromAmp($request);
                $response      = $kernel->handle($serverRequest);
                $eventDispatcher->dispatch(new OnRequest($serverRequest, $response));
                return (new AmpResponseEmitter())->emit($response);
            }), $logger);
            $this->showInfo();
            yield $server->start();

            // Stop the server gracefully when SIGINT is received.
            // This is technically optional, but it is best to call Server::stop().
            Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                Loop::cancel($watcherId);
                yield $server->stop();
            });
        });

        return 0;
    }
}

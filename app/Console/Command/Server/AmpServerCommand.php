<?php

namespace App\Console\Command\Server;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Socket\Server;
use App\Console\Command\Exception;
use App\Http\Kernel;
use App\Http\ServerRequest;
use App\Logger;
use Max\Di\Context;
use Max\Http\Server\ResponseEmitter\AmpResponseEmitter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AmpServerCommand extends BaseServerCommand
{
    protected string $container = 'AmpPHP';

    protected function configure()
    {
        $this->setName('serve:amp')
             ->setDescription('Start AmpPHP server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists('Amp\Http\Server\HttpServer')) {
            throw new Exception('You should install the amphp/http-server package before starting.');
        }

        (function () {
            $container = Context::getContainer();
            $kernel    = $container->make(Kernel::class);
            $logger    = $container->make(Logger::class)->get();

            Amp\Loop::run(function () use ($kernel, $logger) {
                $sockets = [
                    Server::listen("{$this->host}:{$this->port}"),
                    //                    Server::listen("[::]:{$port}"),
                ];

                $server = new HttpServer($sockets, new CallableRequestHandler(
                    fn(Request $request) => (new AmpResponseEmitter())->emit($kernel->handle(ServerRequest::createFromAmp($request)))
                ), $logger);
                $this->showInfo();
                yield $server->start();

                // Stop the server gracefully when SIGINT is received.
                // This is technically optional, but it is best to call Server::stop().
                Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                    Amp\Loop::cancel($watcherId);
                    yield $server->stop();
                });
            });
        })();

        return 0;
    }
}
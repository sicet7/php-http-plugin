<?php

namespace Sicet7\HTTP\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sicet7\HTTP\Exceptions\HttpException;
use Sicet7\HTTP\Interfaces\AcceptsMiddlewareInterface;
use Sicet7\HTTP\Interfaces\HandlerContainerInterface;
use Sicet7\HTTP\RequestAttributes\PathArguments;
use Sicet7\HTTP\RequestAttributes\RoutingResult;

class RoutingHandler implements RequestHandlerInterface, AcceptsMiddlewareInterface
{
    /**
     * @var RequestHandlerInterface
     */
    private RequestHandlerInterface $handler;

    /**
     * @param MiddlewareInterface $routingMiddleware
     * @param HandlerContainerInterface $handlerContainer
     */
    public function __construct(
        MiddlewareInterface $routingMiddleware,
        private readonly HandlerContainerInterface $handlerContainer
    ) {
        $this->handler = new class($this) implements RequestHandlerInterface {

            public function __construct(private readonly RoutingHandler $routingHandler)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->routingHandler->handleRouting($request);
            }
        };
        $this->addMiddleware($routingMiddleware);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws HttpException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRouting(ServerRequestInterface $request): ResponseInterface
    {
        $routingResult = RoutingResult::readAttribute($request)?->result;
        if ($routingResult === null) {
            throw new \RuntimeException('Routing was not performed before handleRouting invocation.');
        }
        if ($routingResult instanceof HttpException) {
            throw $routingResult;
        }
        $arguments = new PathArguments($routingResult->vars);
        return $this->handlerContainer
            ->getHandler($routingResult->handlerId)
            ->handle($arguments->withAttribute($request));
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->handler = new MiddlewareHandler($this->handler, $middleware);
    }
}
<?php

namespace Sicet7\HTTP;

use FastRoute\Dispatcher as DispatcherInterface;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Psr\Http\Server\RequestHandlerInterface;
use Sicet7\HTTP\Handlers\MiddlewareStackHandler;
use Sicet7\HTTP\Handlers\RouteInvokerHandler;
use Sicet7\HTTP\Interfaces\AcceptsMiddlewareInterface;
use Sicet7\HTTP\Interfaces\HandlerContainerInterface;
use Sicet7\HTTP\Interfaces\RouteCollectorInterface;
use Sicet7\HTTP\Middlewares\FastRouteDispatcherMiddleware;
use Sicet7\Plugin\Container\Interfaces\PluginInterface;
use FastRoute\RouteParser\Std as RouteParser;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\DataGenerator as DataGeneratorInterface;
use FastRoute\RouteParser as RouteParserInterface;
use FastRoute\RouteCollector as FastRouteRouteCollector;
use Sicet7\Plugin\Container\MutableDefinitionSourceHelper;

final class HttpPlugin implements PluginInterface
{
    /**
     * @param MutableDefinitionSourceHelper $source
     * @return void
     */
    public function register(MutableDefinitionSourceHelper $source): void
    {
        //RouteParser
        $source->object(RouteParser::class, RouteParser::class);
        $source->reference(RouteParserInterface::class, RouteParser::class);

        //DataGenerator
        $source->object(DataGenerator::class, DataGenerator::class);
        $source->reference(DataGeneratorInterface::class, DataGenerator::class);

        //RouteCollectorProxy
        $source->object(RouteCollectorProxy::class, RouteCollectorProxy::class);
        $source->reference(HandlerContainerInterface::class, RouteCollectorProxy::class);
        $source->reference(RouteCollectorInterface::class, RouteCollectorProxy::class);

        //RouteInvokerHandler
        $source->autowire(RouteInvokerHandler::class, RouteInvokerHandler::class);

        //MiddlewareStackHandler
        $source->factory(
            MiddlewareStackHandler::class,
            function (
                FastRouteDispatcherMiddleware $routingMiddleware,
                RouteInvokerHandler $routeInvoker
            ): MiddlewareStackHandler {
                //Sets the route invoker as the root handler
                $handler = new MiddlewareStackHandler($routeInvoker);

                //Adds the routing middleware as the innermost middleware
                $handler->addMiddleware($routingMiddleware);
                return $handler;
            }
        );
        $source->reference(RequestHandlerInterface::class, MiddlewareStackHandler::class);
        $source->reference(AcceptsMiddlewareInterface::class, MiddlewareStackHandler::class);

        //FastRouteRouteCollector
        $source->factory(
            FastRouteRouteCollector::class,
            function (
                RouteParserInterface $routeParser,
                DataGeneratorInterface $dataGenerator,
                RouteCollectorInterface $routeCollector
            ): FastRouteRouteCollector {
                $collector = new FastRouteRouteCollector($routeParser, $dataGenerator);
                $routeCollector->apply($collector);
                return $collector;
            }
        );

        //GroupCountBasedDispatcher
        $source->factory(
            GroupCountBasedDispatcher::class,
            function (
                FastRouteRouteCollector $routeCollector
            ): GroupCountBasedDispatcher {
                return new GroupCountBasedDispatcher($routeCollector->getData());
            }
        );
        $source->reference(DispatcherInterface::class, GroupCountBasedDispatcher::class);

        //FastRouteDispatcherMiddleware
        $source->autowire(FastRouteDispatcherMiddleware::class, FastRouteDispatcherMiddleware::class);
    }
}
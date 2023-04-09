<?php

namespace Sicet7\HTTP;

use DI\Definition\FactoryDefinition;
use DI\Definition\ObjectDefinition;
use DI\Definition\ObjectDefinition\MethodInjection;
use DI\Definition\Reference;
use DI\Definition\Source\MutableDefinitionSource;
use FastRoute\Dispatcher as DispatcherInterface;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Psr\Http\Server\RequestHandlerInterface;
use Sicet7\HTTP\Handlers\RoutingHandler;
use Sicet7\HTTP\Interfaces\AcceptsMiddlewareInterface;
use Sicet7\HTTP\Interfaces\HandlerContainerInterface;
use Sicet7\HTTP\Interfaces\RouteCollectorInterface;
use Sicet7\HTTP\Middlewares\FastRouteDispatcherMiddleware;
use Sicet7\Plugin\Container\Interfaces\PluginInterface;
use FastRoute\RouteParser\Std as RouteParser;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
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

        //DataGenerator
        $source->object(DataGenerator::class, DataGenerator::class);

        //RouteCollectorProxy
        $source->object(RouteCollectorProxy::class, RouteCollectorProxy::class);
        $source->reference(HandlerContainerInterface::class, RouteCollectorProxy::class);
        $source->reference(RouteCollectorInterface::class, RouteCollectorProxy::class);

        //RoutingHandler
        $source->factory(
            RoutingHandler::class,
            function (
                FastRouteDispatcherMiddleware $routingMiddleware,
                HandlerContainerInterface $handlerContainer
            ): RoutingHandler {
                return new RoutingHandler($routingMiddleware, $handlerContainer);
            }
        );
        $source->reference(RequestHandlerInterface::class, RoutingHandler::class);
        $source->reference(AcceptsMiddlewareInterface::class, RoutingHandler::class);

        //FastRouteRouteCollector
        $source->factory(
            FastRouteRouteCollector::class,
            function (
                RouteParser $routeParser,
                DataGenerator $dataGenerator,
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
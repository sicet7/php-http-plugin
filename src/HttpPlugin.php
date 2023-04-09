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

final class HttpPlugin implements PluginInterface
{
    /**
     * @param MutableDefinitionSource $source
     * @return void
     */
    public function register(MutableDefinitionSource $source): void
    {
        //RouteParser
        $source->addDefinition(new ObjectDefinition(RouteParser::class, RouteParser::class));

        //DataGenerator
        $source->addDefinition(new ObjectDefinition(DataGenerator::class, DataGenerator::class));

        //FastRouteRouteCollector
        $source->addDefinition(new FactoryDefinition(
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
        ));

        //GroupCountBasedDispatcher
        $source->addDefinition(new FactoryDefinition(
            GroupCountBasedDispatcher::class,
            function (
                FastRouteRouteCollector $routeCollector
            ): GroupCountBasedDispatcher {
                return new GroupCountBasedDispatcher($routeCollector->getData());
            }
        ));
        $source->addDefinition($this->makeRef(DispatcherInterface::class, GroupCountBasedDispatcher::class));

        //FastRouteDispatcherMiddleware
        $routingMiddlewareDefinition = new ObjectDefinition(
            FastRouteDispatcherMiddleware::class,
            FastRouteDispatcherMiddleware::class
        );
        $routingMiddlewareDefinition->setConstructorInjection(MethodInjection::constructor([
            new Reference(DispatcherInterface::class)
        ]));
        $source->addDefinition($routingMiddlewareDefinition);

        //RouteCollectorProxy
        $source->addDefinition(new ObjectDefinition(RouteCollectorProxy::class, RouteCollectorProxy::class));
        $source->addDefinition($this->makeRef(HandlerContainerInterface::class, RouteCollectorProxy::class));
        $source->addDefinition($this->makeRef(RouteCollectorInterface::class, RouteCollectorProxy::class));

        //RoutingHandler
        $routingHandlerDef = new ObjectDefinition(RoutingHandler::class, RoutingHandler::class);
        $routingHandlerDef->setConstructorInjection(MethodInjection::constructor([
            new Reference(FastRouteDispatcherMiddleware::class),
            new Reference(HandlerContainerInterface::class)
        ]));
        $source->addDefinition($routingHandlerDef);
        $source->addDefinition($this->makeRef(RequestHandlerInterface::class, RoutingHandler::class));
        $source->addDefinition($this->makeRef(AcceptsMiddlewareInterface::class, RoutingHandler::class));
    }

    /**
     * @param string $name
     * @param string $target
     * @return Reference
     */
    private function makeRef(string $name, string $target): Reference
    {
        $ref = new Reference($target);
        $ref->setName($name);
        return $ref;
    }
}
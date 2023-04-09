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
        $source->addDefinition(new FactoryDefinition(
            RouteParser::class,
            function (): RouteParser {
                return new RouteParser();
            }
        ));

        //DataGenerator
        $source->addDefinition(new FactoryDefinition(
            DataGenerator::class,
            function (): DataGenerator {
                return new DataGenerator();
            }
        ));

        //RouteCollectorProxy
        $source->addDefinition(new FactoryDefinition(
            RouteCollectorProxy::class,
            function(): RouteCollectorProxy {
                return new RouteCollectorProxy();
            }
        ));
        $source->addDefinition($this->makeRef(HandlerContainerInterface::class, RouteCollectorProxy::class));
        $source->addDefinition($this->makeRef(RouteCollectorInterface::class, RouteCollectorProxy::class));

        //RoutingHandler
        $source->addDefinition(new FactoryDefinition(
            RoutingHandler::class,
            function (
                FastRouteDispatcherMiddleware $routingMiddleware,
                HandlerContainerInterface $handlerContainer
            ): RoutingHandler {
                return new RoutingHandler($routingMiddleware, $handlerContainer);
            }
        ));
        $source->addDefinition($this->makeRef(RequestHandlerInterface::class, RoutingHandler::class));
        $source->addDefinition($this->makeRef(AcceptsMiddlewareInterface::class, RoutingHandler::class));

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
        $source->addDefinition(new FactoryDefinition(
            FastRouteDispatcherMiddleware::class,
            function (
                DispatcherInterface $dispatcher
            ): FastRouteDispatcherMiddleware {
                return new FastRouteDispatcherMiddleware($dispatcher);
            }
        ));
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
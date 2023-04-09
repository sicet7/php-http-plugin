<?php

namespace Sicet7\HTTP;

use Psr\Http\Server\RequestHandlerInterface;
use Sicet7\HTTP\Enums\HttpMethod;
use Sicet7\HTTP\Interfaces\HandlerContainerInterface;
use Sicet7\HTTP\Interfaces\RouteCollectorInterface;
use Sicet7\HTTP\Interfaces\RouteInterface;
use FastRoute\RouteCollector as FastRouteRouteCollector;

class RouteCollectorProxy implements HandlerContainerInterface, RouteCollectorInterface
{
    /**
     * @var RouteInterface[]
     */
    private array $routes = [];

    public function __construct(
        private readonly FastRouteRouteCollector $routeCollector
    ) {
    }

    /**
     * @param string $id
     * @return RequestHandlerInterface
     */
    public function getHandler(string $id): RequestHandlerInterface
    {
        return $this->routes[$id];
    }

    /**
     * @param RouteInterface $route
     * @return void
     */
    public function add(RouteInterface $route): void
    {
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCollector->addRoute(
            array_map(fn(HttpMethod $method) => $method->value, $route->getMethods()),
            $route->getPattern(),
            $route->getIdentifier()
        );
    }
}
<?php

namespace Sicet7\HTTP\Interfaces;

interface RouteCollectorInterface
{
    /**
     * @param RouteInterface $route
     * @return void
     */
    public function add(RouteInterface $route): void;
}
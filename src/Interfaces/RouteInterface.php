<?php

namespace Sicet7\HTTP\Interfaces;

use Psr\Http\Server\RequestHandlerInterface;
use Sicet7\HTTP\Enums\HttpMethod;

interface RouteInterface extends RequestHandlerInterface, HasIdentifierInterface, AcceptsMiddlewareInterface
{
    /**
     * @return HttpMethod[]
     */
    public function getMethods(): array;

    /**
     * @return string
     */
    public function getPattern(): string;
}
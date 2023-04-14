<?php

namespace Sicet7\HTTP\Interfaces;

use Psr\Http\Server\RequestHandlerInterface;
use Sicet7\HttpUtils\Enums\Method;

interface RouteInterface extends RequestHandlerInterface, HasIdentifierInterface, AcceptsMiddlewareInterface
{
    /**
     * @return Method[]
     */
    public function getMethods(): array;

    /**
     * @return string
     */
    public function getPattern(): string;
}
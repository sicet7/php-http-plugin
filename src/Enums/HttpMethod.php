<?php

namespace Sicet7\HTTP\Enums;

enum HttpMethod: string
{
    case HEAD = 'HEAD';
    case GET = 'GET';
    case POST = 'POST';
    case PATCH = 'PATCH';
    case PUT = 'PUT';
    case OPTIONS = 'OPTIONS';
    case DELETE = 'DELETE';
}
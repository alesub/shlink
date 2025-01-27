<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;

class EmptyResponseImplicitOptionsMiddlewareFactory
{
    public function __invoke()
    {
        return new ImplicitOptionsMiddleware(function () {
            return new EmptyResponse();
        });
    }
}

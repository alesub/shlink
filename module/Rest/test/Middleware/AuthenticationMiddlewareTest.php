<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Rest\Action\AuthenticateAction;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthenticationPluginInterface;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPluginInterface;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;

use function Zend\Stratigility\middleware;

class AuthenticationMiddlewareTest extends TestCase
{
    /** @var AuthenticationMiddleware */
    private $middleware;
    /** @var ObjectProphecy */
    private $requestToPlugin;
    /** @var ObjectProphecy */
    private $logger;

    public function setUp(): void
    {
        $this->requestToPlugin = $this->prophesize(RequestToHttpAuthPluginInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->middleware = new AuthenticationMiddleware(
            $this->requestToPlugin->reveal(),
            [AuthenticateAction::class],
            $this->logger->reveal()
        );
    }

    /**
     * @test
     * @dataProvider provideWhitelistedRequests
     */
    public function someWhiteListedSituationsFallbackToNextMiddleware(ServerRequestInterface $request): void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle($request)->willReturn(new Response());
        $fromRequest = $this->requestToPlugin->fromRequest(Argument::any())->willReturn(
            $this->prophesize(AuthenticationPluginInterface::class)->reveal()
        );

        $this->middleware->process($request, $handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $fromRequest->shouldNotHaveBeenCalled();
    }

    public function provideWhitelistedRequests(): iterable
    {
        $dummyMiddleware = $this->getDummyMiddleware();

        yield 'with no route result' => [new ServerRequest()];
        yield 'with failure route result' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteFailure([RequestMethodInterface::METHOD_GET])
        )];
        yield 'with whitelisted route' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(
                new Route('foo', $dummyMiddleware, Route::HTTP_METHOD_ANY, AuthenticateAction::class)
            )
        )];
        yield 'with OPTIONS method' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $dummyMiddleware), [])
        )->withMethod(RequestMethodInterface::METHOD_OPTIONS)];
    }

    /** @test */
    public function updatedResponseIsReturnedWhenVerificationPasses(): void
    {
        $newResponse = new Response();
        $request = (new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), [])
        );
        $plugin = $this->prophesize(AuthenticationPluginInterface::class);

        $verify = $plugin->verify($request)->will(function () {
        });
        $update = $plugin->update($request, Argument::type(ResponseInterface::class))->willReturn($newResponse);
        $fromRequest = $this->requestToPlugin->fromRequest(Argument::any())->willReturn($plugin->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle($request)->willReturn(new Response());
        $response = $this->middleware->process($request, $handler->reveal());

        $this->assertSame($response, $newResponse);
        $verify->shouldHaveBeenCalledOnce();
        $update->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
        $fromRequest->shouldHaveBeenCalledOnce();
    }

    private function getDummyMiddleware(): MiddlewareInterface
    {
        return middleware(function () {
            return new Response\EmptyResponse();
        });
    }
}

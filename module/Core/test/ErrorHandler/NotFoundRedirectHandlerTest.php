<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ErrorHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\ErrorHandler\NotFoundRedirectHandler;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;

class NotFoundRedirectHandlerTest extends TestCase
{
    /** @var NotFoundRedirectHandler */
    private $middleware;
    /** @var NotFoundRedirectOptions */
    private $redirectOptions;

    public function setUp(): void
    {
        $this->redirectOptions = new NotFoundRedirectOptions();
        $this->middleware = new NotFoundRedirectHandler($this->redirectOptions, '');
    }

    /**
     * @test
     * @dataProvider provideRedirects
     */
    public function expectedRedirectionIsReturnedDependingOnTheCase(
        ServerRequestInterface $request,
        string $expectedRedirectTo
    ): void {
        $this->redirectOptions->invalidShortUrl = 'invalidShortUrl';
        $this->redirectOptions->regular404 = 'regular404';
        $this->redirectOptions->baseUrl = 'baseUrl';

        $next = $this->prophesize(RequestHandlerInterface::class);
        $handle = $next->handle($request)->willReturn(new Response());

        $resp = $this->middleware->process($request, $next->reveal());

        $this->assertInstanceOf(Response\RedirectResponse::class, $resp);
        $this->assertEquals($expectedRedirectTo, $resp->getHeaderLine('Location'));
        $handle->shouldNotHaveBeenCalled();
    }

    public function provideRedirects(): iterable
    {
        yield 'base URL with trailing slash' => [
            ServerRequestFactory::fromGlobals()->withUri(new Uri('/')),
            'baseUrl',
        ];
        yield 'base URL without trailing slash' => [
            ServerRequestFactory::fromGlobals()->withUri(new Uri('')),
            'baseUrl',
        ];
        yield 'regular 404' => [
            ServerRequestFactory::fromGlobals()->withUri(new Uri('/foo/bar')),
            'regular404',
        ];
        yield 'invalid short URL' => [
            ServerRequestFactory::fromGlobals()
                ->withAttribute(
                    RouteResult::class,
                    RouteResult::fromRoute(
                        new Route(
                            '',
                            $this->prophesize(MiddlewareInterface::class)->reveal(),
                            ['GET'],
                            RedirectAction::class
                        )
                    )
                )
                ->withUri(new Uri('/abc123')),
            'invalidShortUrl',
        ];
    }

    /** @test */
    public function nextMiddlewareIsInvokedWhenNotRedirectNeedsToOccur(): void
    {
        $req = ServerRequestFactory::fromGlobals();
        $resp = new Response();

        $next = $this->prophesize(RequestHandlerInterface::class);
        $handle = $next->handle($req)->willReturn($resp);

        $result = $this->middleware->process($req, $next->reveal());

        $this->assertSame($resp, $result);
        $handle->shouldHaveBeenCalledOnce();
    }
}

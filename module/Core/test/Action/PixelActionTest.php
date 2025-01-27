<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;
use Shlinkio\Shlink\Core\Action\PixelAction;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Zend\Diactoros\ServerRequest;

class PixelActionTest extends TestCase
{
    /** @var RedirectAction */
    private $action;
    /** @var ObjectProphecy */
    private $urlShortener;
    /** @var ObjectProphecy */
    private $visitTracker;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->visitTracker = $this->prophesize(VisitsTracker::class);

        $this->action = new PixelAction(
            $this->urlShortener->reveal(),
            $this->visitTracker->reveal(),
            new AppOptions()
        );
    }

    /** @test */
    public function imageIsReturned(): void
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode, '')->willReturn(
            new ShortUrl('http://domain.com/foo/bar')
        )->shouldBeCalledOnce();
        $this->visitTracker->track(Argument::cetera())->shouldBeCalledOnce();

        $request = (new ServerRequest())->withAttribute('shortCode', $shortCode);
        $response = $this->action->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());

        $this->assertInstanceOf(PixelResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/gif', $response->getHeaderLine('content-type'));
    }
}

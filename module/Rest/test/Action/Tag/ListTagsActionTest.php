<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\ListTagsAction;
use Zend\Diactoros\ServerRequest;

use function Shlinkio\Shlink\Common\json_decode;

class ListTagsActionTest extends TestCase
{
    /** @var ListTagsAction */
    private $action;
    /** @var ObjectProphecy */
    private $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new ListTagsAction($this->tagService->reveal());
    }

    /** @test */
    public function returnsDataFromService()
    {
        $listTags = $this->tagService->listTags()->willReturn([new Tag('foo'), new Tag('bar')]);

        $resp = $this->action->handle(new ServerRequest());

        $this->assertEquals([
            'tags' => [
                'data' => ['foo', 'bar'],
            ],
        ], json_decode((string) $resp->getBody()));
        $listTags->shouldHaveBeenCalled();
    }
}

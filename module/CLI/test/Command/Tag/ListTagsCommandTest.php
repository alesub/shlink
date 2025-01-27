<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Tag\ListTagsCommand;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListTagsCommandTest extends TestCase
{
    /** @var ListTagsCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);

        $command = new ListTagsCommand($this->tagService->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function noTagsPrintsEmptyMessage()
    {
        $listTags = $this->tagService->listTags()->willReturn([]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('No tags yet', $output);
        $listTags->shouldHaveBeenCalled();
    }

    /** @test */
    public function listOfTagsIsPrinted()
    {
        $listTags = $this->tagService->listTags()->willReturn([
            new Tag('foo'),
            new Tag('bar'),
        ]);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('bar', $output);
        $listTags->shouldHaveBeenCalled();
    }
}

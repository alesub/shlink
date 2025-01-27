<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GenerateShortUrlCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateShortUrlCommandTest extends TestCase
{
    private const DOMAIN_CONFIG = [
        'schema' => 'http',
        'hostname' => 'foo.com',
    ];

    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $urlShortener;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $command = new GenerateShortUrlCommand($this->urlShortener->reveal(), self::DOMAIN_CONFIG);
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function properShortCodeIsCreatedIfLongUrlIsCorrect(): void
    {
        $shortUrl = new ShortUrl('');
        $urlToShortCode = $this->urlShortener->urlToShortCode(Argument::cetera())->willReturn($shortUrl);

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--maxVisits' => '3',
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString($shortUrl->toString(self::DOMAIN_CONFIG), $output);
        $urlToShortCode->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function exceptionWhileParsingLongUrlOutputsError(): void
    {
        $url = 'http://domain.com/invalid';
        $this->urlShortener->urlToShortCode(Argument::cetera())->willThrow(InvalidUrlException::fromUrl($url))
                                                               ->shouldBeCalledOnce();

        $this->commandTester->execute(['longUrl' => $url]);
        $output = $this->commandTester->getDisplay();

        $this->assertEquals(ExitCodes::EXIT_FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Provided URL http://domain.com/invalid is invalid.', $output);
    }

    /** @test */
    public function providingNonUniqueSlugOutputsError(): void
    {
        $urlToShortCode = $this->urlShortener->urlToShortCode(Argument::cetera())->willThrow(
            NonUniqueSlugException::fromSlug('my-slug')
        );

        $this->commandTester->execute(['longUrl' => 'http://domain.com/invalid', '--customSlug' => 'my-slug']);
        $output = $this->commandTester->getDisplay();

        $this->assertEquals(ExitCodes::EXIT_FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Provided slug "my-slug" is already in use', $output);
        $urlToShortCode->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function properlyProcessesProvidedTags(): void
    {
        $shortUrl = new ShortUrl('');
        $urlToShortCode = $this->urlShortener->urlToShortCode(
            Argument::type(UriInterface::class),
            Argument::that(function (array $tags) {
                Assert::assertEquals(['foo', 'bar', 'baz', 'boo', 'zar'], $tags);
                return $tags;
            }),
            Argument::cetera()
        )->willReturn($shortUrl);

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--tags' => ['foo,bar', 'baz', 'boo,zar,baz'],
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString($shortUrl->toString(self::DOMAIN_CONFIG), $output);
        $urlToShortCode->shouldHaveBeenCalledOnce();
    }
}

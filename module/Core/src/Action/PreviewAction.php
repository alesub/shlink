<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\ResponseUtilsTrait;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\PreviewGenerator\Exception\PreviewGenerationException;
use Shlinkio\Shlink\PreviewGenerator\Service\PreviewGeneratorInterface;

/** @deprecated */
class PreviewAction implements MiddlewareInterface
{
    use ResponseUtilsTrait;

    /** @var PreviewGeneratorInterface */
    private $previewGenerator;
    /** @var UrlShortenerInterface */
    private $urlShortener;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PreviewGeneratorInterface $previewGenerator,
        UrlShortenerInterface $urlShortener,
        ?LoggerInterface $logger = null
    ) {
        $this->previewGenerator = $previewGenerator;
        $this->urlShortener = $urlShortener;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $shortCode = $request->getAttribute('shortCode');

        try {
            $url = $this->urlShortener->shortCodeToUrl($shortCode);
            $imagePath = $this->previewGenerator->generatePreview($url->getLongUrl());
            return $this->generateImageResponse($imagePath);
        } catch (ShortUrlNotFoundException | PreviewGenerationException $e) {
            $this->logger->warning('An error occurred while generating preview image. {e}', ['e' => $e]);
            return $handler->handle($request);
        }
    }
}

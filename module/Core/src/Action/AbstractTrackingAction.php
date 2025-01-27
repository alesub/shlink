<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Zend\Diactoros\Uri;

use function array_key_exists;
use function array_merge;
use function GuzzleHttp\Psr7\parse_query;
use function http_build_query;

abstract class AbstractTrackingAction implements MiddlewareInterface
{
    /** @var UrlShortenerInterface */
    private $urlShortener;
    /** @var VisitsTrackerInterface */
    private $visitTracker;
    /** @var AppOptions */
    private $appOptions;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        VisitsTrackerInterface $visitTracker,
        AppOptions $appOptions,
        ?LoggerInterface $logger = null
    ) {
        $this->urlShortener = $urlShortener;
        $this->visitTracker = $visitTracker;
        $this->appOptions = $appOptions;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $domain = $request->getUri()->getAuthority();
        $query = $request->getQueryParams();
        $disableTrackParam = $this->appOptions->getDisableTrackParam();

        try {
            $url = $this->urlShortener->shortCodeToUrl($shortCode, $domain);

            // Track visit to this short code
            if ($disableTrackParam === null || ! array_key_exists($disableTrackParam, $query)) {
                $this->visitTracker->track($shortCode, Visitor::fromRequest($request));
            }

            return $this->createSuccessResp($this->buildUrlToRedirectTo($url, $query, $disableTrackParam));
        } catch (ShortUrlNotFoundException $e) {
            $this->logger->warning('An error occurred while tracking short code. {e}', ['e' => $e]);
            return $this->createErrorResp($request, $handler);
        }
    }

    private function buildUrlToRedirectTo(ShortUrl $shortUrl, array $currentQuery, ?string $disableTrackParam): string
    {
        $uri = new Uri($shortUrl->getLongUrl());
        $hardcodedQuery = parse_query($uri->getQuery());
        if ($disableTrackParam !== null) {
            unset($currentQuery[$disableTrackParam]);
        }
        $mergedQuery = array_merge($hardcodedQuery, $currentQuery);

        return (string) $uri->withQuery(http_build_query($mergedQuery));
    }

    abstract protected function createSuccessResp(string $longUrl): ResponseInterface;

    abstract protected function createErrorResp(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\JsonResponse;

class ListShortUrlsAction extends AbstractRestAction
{
    use PaginatorUtilsTrait;

    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /** @var ShortUrlServiceInterface */
    private $shortUrlService;
    /** @var array */
    private $domainConfig;

    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        array $domainConfig,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        $params = $this->queryToListParams($request->getQueryParams());
        $shortUrls = $this->shortUrlService->listShortUrls(...$params);
        return new JsonResponse(['shortUrls' => $this->serializePaginator($shortUrls, new ShortUrlDataTransformer(
            $this->domainConfig
        ))]);
    }

    /**
     * @param array $query
     * @return array
     */
    private function queryToListParams(array $query): array
    {
        return [
            (int) ($query['page'] ?? 1),
            $query['searchTerm'] ?? null,
            $query['tags'] ?? [],
            $query['orderBy'] ?? null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

interface ShortUrlRepositoryInterface extends ObjectRepository
{
    /**
     * Gets a list of elements using provided filtering data
     *
     * @param string|array|null $orderBy
     */
    public function findList(
        ?int $limit = null,
        ?int $offset = null,
        ?string $searchTerm = null,
        array $tags = [],
        $orderBy = null
    ): array;

    /**
     * Counts the number of elements in a list using provided filtering data
     */
    public function countList(?string $searchTerm = null, array $tags = []): int;

    public function findOneByShortCode(string $shortCode, ?string $domain = null): ?ShortUrl;

    public function shortCodeIsInUse(string $slug, ?string $domain): bool;
}

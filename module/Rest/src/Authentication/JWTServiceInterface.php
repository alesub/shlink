<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;

interface JWTServiceInterface
{
    public const DEFAULT_LIFETIME = 604800; // 1 week
    public const DEFAULT_ENCRYPTION_ALG = 'HS256';

    /**
     * Creates a new JSON web token por provided API key
     *
     * @param ApiKey $apiKey
     * @param int $lifetime
     * @return string
     */
    public function create(ApiKey $apiKey, $lifetime = self::DEFAULT_LIFETIME): string;

    /**
     * Refreshes a token and returns it with the new expiration
     *
     * @param string $jwt
     * @param int $lifetime
     * @return string
     * @throws AuthenticationException If the token has expired
     */
    public function refresh(string $jwt, $lifetime = self::DEFAULT_LIFETIME): string;

    /**
     * Verifies that certain JWT is valid
     *
     * @param string $jwt
     * @return bool
     */
    public function verify(string $jwt): bool;

    /**
     * Decodes certain token and returns the payload
     *
     * @param string $jwt
     * @return array
     * @throws AuthenticationException If the token has expired
     */
    public function getPayload(string $jwt): array;
}

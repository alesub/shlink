<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class NonUniqueSlugException extends InvalidArgumentException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Invalid custom slug';
    private const TYPE = 'INVALID_SLUG';

    public static function fromSlug(string $slug, ?string $domain = null): self
    {
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $e = new self(sprintf('Provided slug "%s" is already in use%s.', $slug, $suffix));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_BAD_REQUEST;
        $e->additional = ['customSlug' => $slug];

        if ($domain !== null) {
            $e->additional['domain'] = $domain;
        }

        return $e;
    }
}

<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\PreviewGenerator\Service;

use mikehaertl\wkhtmlto\Image;
use Shlinkio\Shlink\PreviewGenerator\Exception\PreviewGenerationException;
use Shlinkio\Shlink\PreviewGenerator\Image\ImageBuilderInterface;
use Symfony\Component\Filesystem\Filesystem;

use function sprintf;
use function urlencode;

/** @deprecated */
class PreviewGenerator implements PreviewGeneratorInterface
{
    /** @var string */
    private $location;
    /** @var ImageBuilderInterface */
    private $imageBuilder;
    /** @var Filesystem */
    private $filesystem;

    public function __construct(ImageBuilderInterface $imageBuilder, Filesystem $filesystem, string $location)
    {
        $this->location = $location;
        $this->imageBuilder = $imageBuilder;
        $this->filesystem = $filesystem;
    }

    /**
     * Generates and stores preview for provided website and returns the path to the image file
     *
     * @throws PreviewGenerationException
     */
    public function generatePreview(string $url): string
    {
        $image = $this->imageBuilder->build(Image::class, ['url' => $url]);

        // If the file already exists, return its path
        $cacheId = sprintf('preview_%s.%s', urlencode($url), $image->type);
        $path = $this->location . '/' . $cacheId;
        if ($this->filesystem->exists($path)) {
            return $path;
        }

        // Save and check if an error occurred
        $image->saveAs($path);
        $error = $image->getError();
        if (! empty($error)) {
            throw PreviewGenerationException::fromImageError($error);
        }

        // Cache the path and return it
        return $path;
    }
}

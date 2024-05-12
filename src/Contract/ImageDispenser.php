<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Intervention\Image\Interfaces\ImageInterface;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

interface ImageDispenser
{
    /**
     * @return ImageInterface|Response
     */
    public function getImageThumbnail(Thumbnail $demand);

    /**
     * Get a resized Guided Image.
     *
     * @return ImageInterface|Response
     */
    public function getResizedImage(Resize $demand);

    /**
     * Get dummy Guided Image.
     *
     * @throws RuntimeException
     */
    public function getDummyImage(Dummy $demand): ImageInterface;

    public function emptyCache(): bool;
}

<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Intervention\Image\Image;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

interface ImageDispenser
{
    /**
     * @return Image|Response
     */
    public function getImageThumbnail(Thumbnail $demand);

    /**
     * Get a resized Guided Image.
     *
     * @return Image|Response
     */
    public function getResizedImage(Resize $demand);

    /**
     * Get dummy Guided Image.
     *
     * @throws RuntimeException
     */
    public function getDummyImage(Dummy $demand): Image;

    public function emptyCache(): bool;
}

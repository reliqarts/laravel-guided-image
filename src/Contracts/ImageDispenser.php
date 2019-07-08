<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contracts;

use Illuminate\Http\Response;
use Intervention\Image\Image;
use ReliqArts\GuidedImage\Demands\Dummy;
use ReliqArts\GuidedImage\Demands\Resize;
use ReliqArts\GuidedImage\Demands\Thumbnail;

interface ImageDispenser
{
    /**
     * @param Thumbnail $demand
     *
     * @return Image|Response
     */
    public function getImageThumbnail(Thumbnail $demand);

    /**
     * Get a resized Guided Image.
     *
     * @param Resize $demand
     *
     * @return Image|Response
     */
    public function getResizedImage(Resize $demand);

    /**
     * Get dummy Guided Image.
     *
     * @param Dummy $demand
     *
     * @return Image|Response
     */
    public function getDummyImage(Dummy $demand);

    /**
     * @return bool
     */
    public function emptySkimDirectories(): bool;
}

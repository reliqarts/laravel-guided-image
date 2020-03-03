<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Illuminate\Http\Response;
use Intervention\Image\Image;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;

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
     * @return Image|Response
     */
    public function getDummyImage(Dummy $demand);

    public function emptyCache(): bool;
}

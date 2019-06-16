<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contracts;

use Illuminate\Http\Response;
use Intervention\Image\Image;
use ReliqArts\GuidedImage\DTO\DummyDemand;
use ReliqArts\GuidedImage\DTO\ResizedDemand;
use ReliqArts\GuidedImage\DTO\ThumbnailDemand;

interface ImageDispenser
{
    /**
     * @param ThumbnailDemand $demand
     *
     * @return Image|Response
     */
    public function getImageThumbnail(ThumbnailDemand $demand);

    /**
     * Get a resized Guided Image.
     *
     * @param ResizedDemand $demand
     *
     * @return Image|Response
     */
    public function getImageResized(ResizedDemand $demand);

    /**
     * Get dummy Guided Image.
     *
     * @param DummyDemand $demand
     *
     * @return Image|Response
     */
    public function getDummyImage(DummyDemand $demand);

    /**
     * @return bool
     */
    public function emptyCache(): bool;
}

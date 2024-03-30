<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demand;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contract\GuidedImage;

abstract class ExistingImage extends Image
{
    public function __construct(
        private readonly Request $request,
        private readonly GuidedImage $guidedImage,
        mixed $width,
        mixed $height
    ) {
        parent::__construct($width, $height);
    }

    final public function getRequest(): Request
    {
        return $this->request;
    }

    final public function getGuidedImage(): GuidedImage
    {
        return $this->guidedImage;
    }
}

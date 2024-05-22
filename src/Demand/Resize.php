<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demand;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contract\GuidedImage;

final class Resize extends ExistingImage
{
    public const ROUTE_TYPE_NAME = 'resize';

    public function __construct(
        Request $request,
        GuidedImage $guidedImage,
        mixed $width,
        mixed $height,
        private readonly mixed $maintainAspectRatio = true,
        private readonly mixed $allowUpSizing = null,
        private readonly mixed $returnObject = false
    ) {
        parent::__construct($request, $guidedImage, $width, $height);
    }

    public function maintainAspectRatio(): bool
    {
        return ! $this->isValueConsideredNull($this->maintainAspectRatio);
    }

    public function allowUpSizing(): bool
    {
        return ! $this->isValueConsideredNull($this->allowUpSizing);
    }

    public function returnObject(): bool
    {
        return ! $this->isValueConsideredNull($this->returnObject);
    }
}

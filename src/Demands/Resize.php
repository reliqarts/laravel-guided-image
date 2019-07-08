<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demands;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contracts\GuidedImage;

final class Resize extends ExistingImage
{
    public const ROUTE_TYPE_NAME = 'resize';

    /**
     * @var mixed
     */
    private $maintainAspectRatio;

    /**
     * @var mixed
     */
    private $allowUpSizing;

    /**
     * Resized constructor.
     *
     * @param Request     $request
     * @param GuidedImage $guidedImage
     * @param mixed       $width
     * @param mixed       $height
     * @param mixed       $aspect
     * @param mixed       $upSize
     * @param mixed       $returnObject
     */
    public function __construct(
        Request $request,
        GuidedImage $guidedImage,
        $width,
        $height,
        $aspect = true,
        $upSize = null,
        $returnObject = null
    ) {
        parent::__construct($request, $guidedImage, $width, $height, $returnObject);

        $this->maintainAspectRatio = $aspect;
        $this->allowUpSizing = $upSize;
    }

    /**
     * @return bool
     */
    public function maintainAspectRatio(): bool
    {
        return !$this->isValueConsideredNull($this->maintainAspectRatio);
    }

    /**
     * @return bool
     */
    public function allowUpSizing(): bool
    {
        return !$this->isValueConsideredNull($this->allowUpSizing);
    }
}

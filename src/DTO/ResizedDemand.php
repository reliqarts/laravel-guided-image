<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\DTO;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contracts\Guided;

class ResizedDemand extends ExistingImageDemand
{
    /**
     * @var mixed
     */
    private $maintainAspectRatio;

    /**
     * @var mixed
     */
    private $allowUpSizing;

    /**
     * ResizedDemand constructor.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param mixed   $width
     * @param mixed   $height
     * @param mixed   $aspect
     * @param mixed   $upSize
     * @param mixed   $returnObject
     */
    public function __construct(
        Request $request,
        Guided $guidedImage,
        $width,
        $height,
        $aspect = null,
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

<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demand;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contract\GuidedImage;

abstract class ExistingImage extends Image
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var GuidedImage
     */
    private GuidedImage $guidedImage;

    /**
     * ExistingImage constructor.
     *
     * @param mixed $width
     * @param mixed $height
     * @param mixed $returnObject
     */
    public function __construct(Request $request, GuidedImage $guidedImage, $width, $height, $returnObject = null)
    {
        parent::__construct($width, $height, $returnObject);

        $this->request = $request;
        $this->guidedImage = $guidedImage;
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

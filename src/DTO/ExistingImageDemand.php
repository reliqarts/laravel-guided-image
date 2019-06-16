<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\DTO;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contracts\Guided;

abstract class ExistingImageDemand extends ImageDemand
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Guided
     */
    private $guidedImage;

    /**
     * ExistingImageDemand constructor.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param mixed   $width
     * @param mixed   $height
     * @param mixed   $returnObject
     */
    public function __construct(Request $request, Guided $guidedImage, $width, $height, $returnObject = null)
    {
        parent::__construct($width, $height, $returnObject);

        $this->request = $request;
        $this->guidedImage = $guidedImage;
    }

    /**
     * @return Request
     */
    final public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return Guided
     */
    final public function getGuidedImage(): Guided
    {
        return $this->guidedImage;
    }
}

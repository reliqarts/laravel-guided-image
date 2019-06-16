<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\DTO;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contracts\Guided;

class ThumbnailDemand extends ExistingImageDemand
{
    private const METHOD_CROP = 'crop';
    private const METHOD_FIT = 'fit';
    private const METHODS = [self::METHOD_CROP, self::METHOD_FIT];

    /**
     * @var string
     */
    private $method;

    /**
     * ThumbnailDemand constructor.
     *
     * @param Request $request
     * @param Guided  $guidedImage
     * @param string  $method
     * @param mixed   $width
     * @param mixed   $height
     * @param bool    $returnObject
     */
    public function __construct(
        Request $request,
        Guided $guidedImage,
        string $method,
        $width,
        $height,
        bool $returnObject = null
    ) {
        parent::__construct($request, $guidedImage, $width, $height, $returnObject);

        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return in_array($this->method, self::METHODS, true);
    }
}

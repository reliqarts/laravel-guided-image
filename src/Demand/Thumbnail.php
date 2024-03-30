<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demand;

use Illuminate\Http\Request;
use ReliqArts\GuidedImage\Contract\GuidedImage;

final class Thumbnail extends ExistingImage
{
    public const ROUTE_TYPE_NAME = 'thumb';

    private const METHOD_CROP = 'crop';

    private const METHOD_COVER = 'cover';

    private const METHOD_FIT = 'fit';

    private const METHODS = [self::METHOD_CROP, self::METHOD_FIT, self::METHOD_COVER];

    public function __construct(
        Request $request,
        GuidedImage $guidedImage,
        private readonly string $method,
        $width,
        $height,
        private readonly bool $returnObject = false
    ) {
        parent::__construct($request, $guidedImage, $width, $height);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getMethod(): string
    {
        // since intervention/image v3; fit() was replaced by cover() and other methods
        // see https://image.intervention.io/v3/introduction/upgrade
        if ($this->method === self::METHOD_FIT) {
            return self::METHOD_COVER;
        }

        return $this->method;
    }

    public function isValid(): bool
    {
        return in_array($this->method, self::METHODS, true);
    }

    public function returnObject(): bool
    {
        return $this->returnObject;
    }
}

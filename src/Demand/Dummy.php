<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demand;

final class Dummy extends Image
{
    public const DEFAULT_COLOR = 'eefefe';

    /**
     * Dummy constructor.
     */
    public function __construct(
        mixed $width,
        mixed $height,
        private readonly mixed $color = null
    ) {
        parent::__construct($width, $height);
    }

    public function getColor(): string
    {
        return $this->isValueConsideredNull($this->color) ? self::DEFAULT_COLOR : $this->color;
    }
}

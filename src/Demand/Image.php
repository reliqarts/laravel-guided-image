<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demand;

use ReliqArts\GuidedImage\Contract\ImageDemand;

abstract class Image implements ImageDemand
{
    public function __construct(private readonly mixed $width, private readonly mixed $height)
    {
    }

    final public function getWidth(): ?int
    {
        return $this->isValueConsideredNull($this->width) ? null : (int) $this->width;
    }

    final public function getHeight(): ?int
    {
        return $this->isValueConsideredNull($this->height) ? null : (int) $this->height;
    }

    final public function isValueConsideredNull(mixed $value): bool
    {
        return in_array($value, static::NULLS, true);
    }
}

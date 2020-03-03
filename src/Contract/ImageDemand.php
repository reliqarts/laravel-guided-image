<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

interface ImageDemand
{
    public const NULLS = [false, null, 'null', 'false', '_', 'n', '0'];

    public function getWidth(): ?int;

    public function getHeight(): ?int;

    public function returnObject(): bool;

    /**
     * @param mixed $value
     */
    public function isValueConsideredNull($value): bool;
}

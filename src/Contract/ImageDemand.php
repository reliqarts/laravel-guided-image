<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

interface ImageDemand
{
    public const NULLS = [false, null, 'null', 'false', '_', 'n', '0'];

    /**
     * @return null|int
     */
    public function getWidth(): ?int;

    /**
     * @return null|int
     */
    public function getHeight(): ?int;

    /**
     * @return bool
     */
    public function returnObject(): bool;

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValueConsideredNull($value): bool;
}

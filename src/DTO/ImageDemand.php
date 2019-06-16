<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\DTO;

abstract class ImageDemand
{
    protected const NULLS = [false, null, 'null', 'false', '_'];

    /**
     * @var mixed
     */
    private $width;

    /**
     * @var mixed
     */
    private $height;

    /**
     * @var mixed
     */
    private $returnObject;

    /**
     * ImageDemand constructor.
     *
     * @param mixed $width
     * @param mixed $height
     * @param mixed $returnObject
     */
    public function __construct($width, $height, $returnObject = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->returnObject = $returnObject;
    }

    /**
     * @return null|int
     */
    final public function getWidth(): ?int
    {
        return $this->isValueConsideredNull($this->width) ? null : (int)$this->width;
    }

    /**
     * @return null|int
     */
    final public function getHeight(): ?int
    {
        return $this->isValueConsideredNull($this->height) ? null : (int)$this->height;
    }

    /**
     * @return bool
     */
    final public function returnObject(): bool
    {
        return !$this->isValueConsideredNull($this->returnObject);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    final protected function isValueConsideredNull($value): bool
    {
        return in_array($value, static::NULLS, true);
    }
}

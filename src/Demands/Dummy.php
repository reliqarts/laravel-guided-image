<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Demands;

class Dummy extends Image
{
    public const DEFAULT_COLOR = 'eefefe';

    /**
     * @var string
     */
    private $color;

    /**
     * @var mixed
     */
    private $filling;

    /**
     * DummyDemand constructor.
     *
     * @param mixed $width
     * @param mixed $height
     * @param mixed $color
     * @param mixed $filling
     * @param null  $returnObject
     */
    public function __construct(
        $width,
        $height,
        $color = null,
        $filling = null,
        $returnObject = null
    ) {
        parent::__construct($width, $height, $returnObject);

        $this->color = $color;
        $this->filling = $filling;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->isValueConsideredNull($this->color) ? self::DEFAULT_COLOR : $this->color;
    }

    /**
     * @return mixed
     */
    public function fill()
    {
        return $this->isValueConsideredNull($this->filling) ? null : $this->filling;
    }
}

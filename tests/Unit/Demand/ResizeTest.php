<?php

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use ReliqArts\GuidedImage\Demand\Resize;

/**
 * Class ImageTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Demand\Resize
 *
 * @internal
 */
final class ResizeTest extends TestCase
{
    /**
     * @dataProvider resizeDimensionDataProvider
     * @covers ::__construct
     * @covers ::getWidth
     * @covers ::isValueConsideredNull
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $width
     */
    public function testGetWidth($width, ?int $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            self::DIMENSION
        );

        self::assertSame($expectedResult, $demand->getWidth());
    }

    /**
     * @dataProvider resizeDimensionDataProvider
     * @covers ::__construct
     * @covers ::getHeight
     * @covers ::isValueConsideredNull
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $height
     */
    public function testGetHeight($height, ?int $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::DIMENSION,
            $height
        );

        self::assertSame($expectedResult, $demand->getHeight());
    }

    /**
     * @dataProvider resizeFlagDataProvider
     * @covers ::__construct
     * @covers ::isValueConsideredNull
     * @covers ::maintainAspectRatio
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $maintainAspectRatio
     */
    public function testMaintainAspectRatio($maintainAspectRatio, bool $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::DIMENSION,
            self::DIMENSION,
            $maintainAspectRatio
        );

        self::assertSame($expectedResult, $demand->maintainAspectRatio());
    }

    /**
     * @dataProvider resizeFlagDataProvider
     * @covers ::__construct
     * @covers ::allowUpSizing
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $upSize
     */
    public function testAllowUpSizing($upSize, bool $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::DIMENSION,
            self::DIMENSION,
            true,
            $upSize
        );

        self::assertSame($expectedResult, $demand->allowUpSizing());
    }

    /**
     * @dataProvider resizeFlagDataProvider
     * @covers ::__construct
     * @covers ::returnObject
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $returnObject
     */
    public function testReturnObject($returnObject, bool $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::DIMENSION,
            self::DIMENSION,
            true,
            null,
            $returnObject
        );

        self::assertSame($expectedResult, $demand->returnObject());
    }

    public function resizeDimensionDataProvider(): array
    {
        return [
            [self::DIMENSION, self::DIMENSION],
            ['n', null],
            ['_', null],
            ['false', null],
            ['null', null],
            [false, null],
            [null, null],
        ];
    }

    public function resizeFlagDataProvider(): array
    {
        return [
            [true, true],
            ['n', false],
            ['_', false],
            ['false', false],
            ['null', false],
            [false, false],
            [null, false],
        ];
    }
}

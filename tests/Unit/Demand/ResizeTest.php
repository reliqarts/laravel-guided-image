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
     * @param mixed    $width
     * @param null|int $expectedResult
     */
    public function testGetWidth($width, ?int $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            self::DIMENSION
        );

        $this->assertSame($expectedResult, $demand->getWidth());
    }

    /**
     * @dataProvider resizeDimensionDataProvider
     * @covers ::__construct
     * @covers ::getHeight
     * @covers ::isValueConsideredNull
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed    $height
     * @param null|int $expectedResult
     */
    public function testGetHeight($height, ?int $expectedResult): void
    {
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::DIMENSION,
            $height
        );

        $this->assertSame($expectedResult, $demand->getHeight());
    }

    /**
     * @dataProvider resizeFlagDataProvider
     * @covers ::__construct
     * @covers ::isValueConsideredNull
     * @covers ::maintainAspectRatio
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $maintainAspectRatio
     * @param bool  $expectedResult
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

        $this->assertSame($expectedResult, $demand->maintainAspectRatio());
    }

    /**
     * @dataProvider resizeFlagDataProvider
     * @covers ::__construct
     * @covers ::allowUpSizing
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $upSize
     * @param bool  $expectedResult
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

        $this->assertSame($expectedResult, $demand->allowUpSizing());
    }

    /**
     * @dataProvider resizeFlagDataProvider
     * @covers ::__construct
     * @covers ::returnObject
     * @covers       \ReliqArts\GuidedImage\Demand\Image::__construct
     *
     * @param mixed $returnObject
     * @param bool  $expectedResult
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

        $this->assertSame($expectedResult, $demand->returnObject());
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
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

<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use PHPUnit\Framework\MockObject\MockObject;
use ReliqArts\GuidedImage\Demand\Image;

/**
 * Class ImageTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Demand\Image
 *
 * @internal
 */
final class ImageTest extends TestCase
{
    /**
     * @dataProvider widthAndHeightDataProvider
     * @covers ::__construct
     * @covers ::getWidth
     * @covers ::isValueConsideredNull
     *
     * @param mixed $width
     */
    public function testGetWidth($width, ?int $expectedResult): void
    {
        $demand = $this->getImageDemand($width, self::DIMENSION, null);

        self::assertSame($expectedResult, $demand->getWidth());
    }

    /**
     * @dataProvider widthAndHeightDataProvider
     * @covers ::__construct
     * @covers ::getHeight
     * @covers ::isValueConsideredNull
     *
     * @param mixed $height
     */
    public function testGetHeight($height, ?int $expectedResult): void
    {
        $demand = $this->getImageDemand(self::DIMENSION, $height, null);

        self::assertSame($expectedResult, $demand->getHeight());
    }

    /**
     * @dataProvider imageFlagDataProvider
     * @covers ::__construct
     * @covers ::isValueConsideredNull
     * @covers ::returnObject
     *
     * @param mixed $returnObject
     */
    public function testReturnObject($returnObject, bool $expectedResult): void
    {
        $demand = $this->getImageDemand(self::DIMENSION, self::DIMENSION, $returnObject);

        self::assertSame($expectedResult, $demand->returnObject());
    }

    public function widthAndHeightDataProvider(): array
    {
        return [
            [200, 200],
            [false, null],
            [null, null],
            ['null', null],
            ['false', null],
            ['_', null],
            ['n', null],
            ['0', null],
        ];
    }

    public function imageFlagDataProvider(): array
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

    /**
     * @param $width
     * @param $height
     * @param null $returnObject
     *
     * @return Image|MockObject
     */
    private function getImageDemand($width, $height, $returnObject = null): MockObject
    {
        return $this->getMockBuilder(Image::class)
            ->setConstructorArgs([$width, $height, $returnObject])
            ->getMockForAbstractClass();
    }
}

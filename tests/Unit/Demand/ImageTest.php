<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

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
     * @param mixed    $width
     * @param null|int $expectedResult
     */
    public function testGetWidth($width, ?int $expectedResult)
    {
        $demand = $this->getImageDemand($width, self::DIMENSION, null);

        $this->assertSame($expectedResult, $demand->getWidth());
    }

    /**
     * @dataProvider widthAndHeightDataProvider
     * @covers ::__construct
     * @covers ::getHeight
     * @covers ::isValueConsideredNull
     *
     * @param mixed    $height
     * @param null|int $expectedResult
     */
    public function testGetHeight($height, ?int $expectedResult)
    {
        $demand = $this->getImageDemand(self::DIMENSION, $height, null);

        $this->assertSame($expectedResult, $demand->getHeight());
    }

    /**
     * @dataProvider imageFlagDataProvider
     * @covers ::__construct
     * @covers ::isValueConsideredNull
     * @covers ::returnObject
     *
     * @param mixed $returnObject
     * @param bool  $expectedResult
     */
    public function testReturnObject($returnObject, bool $expectedResult)
    {
        $demand = $this->getImageDemand(self::DIMENSION, self::DIMENSION, $returnObject);

        $this->assertSame($expectedResult, $demand->returnObject());
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
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
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getImageDemand($width, $height, $returnObject = null)
    {
        return $this->getMockBuilder(Image::class)
            ->setConstructorArgs([$width, $height, $returnObject])
            ->getMockForAbstractClass();
    }
}

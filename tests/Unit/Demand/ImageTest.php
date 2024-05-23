<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Image;

/**
 * @internal
 */
#[CoversClass(Image::class)]
final class ImageTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[DataProvider('widthAndHeightDataProvider')]
    public function testGetWidth($width, ?int $expectedResult): void
    {
        $demand = $this->getImageDemand($width, self::DIMENSION);

        self::assertSame($expectedResult, $demand->getWidth());
    }

    /**
     * @throws Exception
     */
    #[DataProvider('widthAndHeightDataProvider')]
    public function testGetHeight($height, ?int $expectedResult): void
    {
        $demand = $this->getImageDemand(self::DIMENSION, $height);

        self::assertSame($expectedResult, $demand->getHeight());
    }

    public static function widthAndHeightDataProvider(): array
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

    private function getImageDemand($width, $height): Image
    {
        return new Dummy($width, $height);
    }
}

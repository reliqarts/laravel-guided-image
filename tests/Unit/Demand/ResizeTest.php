<?php

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReliqArts\GuidedImage\Demand\Resize;

/**
 * @internal
 */
#[CoversClass(Resize::class)]
final class ResizeTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[DataProvider('resizeDimensionDataProvider')]
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
     * @throws Exception
     */
    #[DataProvider('resizeDimensionDataProvider')]
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
     * @throws Exception
     */
    #[DataProvider('resizeFlagDataProvider')]
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
     * @throws Exception
     */
    #[DataProvider('resizeFlagDataProvider')]
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
     * @throws Exception
     */
    #[DataProvider('resizeFlagDataProvider')]
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

    public static function resizeDimensionDataProvider(): array
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

    public static function resizeFlagDataProvider(): array
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

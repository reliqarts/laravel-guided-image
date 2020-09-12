<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use ReliqArts\GuidedImage\Demand\Dummy;

/**
 * Class DummyTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Demand\Dummy
 *
 * @internal
 */
final class DummyTest extends TestCase
{
    /**
     * @dataProvider colorDataProvider
     * @covers ::__construct
     * @covers ::getColor
     * @covers ::isValueConsideredNull
     *
     * @param mixed $color
     */
    public function testGetColor($color, string $expectedResult): void
    {
        $demand = new Dummy(
            self::DIMENSION,
            self::DIMENSION,
            $color
        );

        self::assertSame($expectedResult, $demand->getColor());
    }

    /**
     * @dataProvider fillDataProvider
     * @covers ::__construct
     * @covers ::fill
     * @covers ::isValueConsideredNull
     *
     * @param mixed $fill
     */
    public function testFill($fill, ?string $expectedResult): void
    {
        $demand = new Dummy(
            self::DIMENSION,
            self::DIMENSION,
            null,
            $fill
        );

        self::assertSame($expectedResult, $demand->fill());
    }

    public function colorDataProvider(): array
    {
        return [
            ['0f0', '0f0'],
            ['n', Dummy::DEFAULT_COLOR],
            ['_', Dummy::DEFAULT_COLOR],
            ['false', Dummy::DEFAULT_COLOR],
            ['null', Dummy::DEFAULT_COLOR],
            [false, Dummy::DEFAULT_COLOR],
            [null, Dummy::DEFAULT_COLOR],
        ];
    }

    public function fillDataProvider(): array
    {
        return [
            ['0f0', '0f0'],
            ['n', null],
            ['_', null],
            ['false', null],
            ['null', null],
            [false, null],
            [null, null],
        ];
    }
}

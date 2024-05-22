<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReliqArts\GuidedImage\Demand\Dummy;

/**
 * @internal
 */
#[CoversClass(Dummy::class)]
final class DummyTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[DataProvider('colorDataProvider')]
    public function testGetColor(mixed $color, string $expectedResult): void
    {
        $demand = new Dummy(
            self::DIMENSION,
            self::DIMENSION,
            $color
        );

        self::assertSame($expectedResult, $demand->getColor());
    }

    public static function colorDataProvider(): array
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
}

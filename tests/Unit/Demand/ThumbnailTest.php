<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReliqArts\GuidedImage\Demand\Thumbnail;

/**
 * @internal
 */
#[CoversClass(Thumbnail::class)]
final class ThumbnailTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[DataProvider('isValidDataProvider')]
    public function testIsValid(string $method, bool $expectedResult): void
    {
        $demand = new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $method,
            self::DIMENSION,
            self::DIMENSION
        );

        self::assertSame($expectedResult, $demand->isValid());
    }

    public static function isValidDataProvider(): array
    {
        return [
            ['crop', true],
            ['cover', true],
            ['fit', true],
            ['grab', false],
            ['spook', false],
        ];
    }
}

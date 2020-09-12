<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use ReliqArts\GuidedImage\Demand\Thumbnail;

/**
 * Class ThumbnailTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Demand\Thumbnail
 *
 * @internal
 */
final class ThumbnailTest extends TestCase
{
    /**
     * @dataProvider isValidDataProvider
     * @covers ::__construct
     * @covers ::isValid
     */
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

    public function isValidDataProvider(): array
    {
        return [
            ['fit', true],
            ['crop', true],
            ['grab', false],
            ['spook', false],
        ];
    }
}

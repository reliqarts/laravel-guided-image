<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use ReliqArts\GuidedImage\Demand\ExistingImage;
use ReliqArts\GuidedImage\Demand\Thumbnail;

/**
 * @internal
 */
#[CoversClass(ExistingImage::class)]
final class ExistingImageTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetRequest(): void
    {
        self::assertSame(
            $this->request->reveal(),
            $this->getExistingImageDemand()->getRequest()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetGuidedImage(): void
    {
        self::assertSame(
            $this->guidedImage->reveal(),
            $this->getExistingImageDemand()
                ->getGuidedImage()
        );
    }

    /**
     * @throws Exception
     */
    private function getExistingImageDemand(): ExistingImage
    {
        return new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            'crop',
            self::DIMENSION,
            self::DIMENSION
        );
    }
}

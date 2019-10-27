<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use ReliqArts\GuidedImage\Demand\ExistingImage;

/**
 * Class ExistingImageTest.
 *
 * @coversDefaultClass \ReliqArts\GuidedImage\Demand\ExistingImage
 *
 * @internal
 */
final class ExistingImageTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getRequest
     */
    public function testGetRequest()
    {
        $demand = $this->getExistingImageDemand(self::DIMENSION, self::DIMENSION, null);

        $this->assertSame($this->request->reveal(), $demand->getRequest());
    }

    /**
     * @covers ::__construct
     * @covers ::getGuidedImage
     */
    public function testGetGuidedImage()
    {
        $demand = $this->getExistingImageDemand(self::DIMENSION, self::DIMENSION, null);

        $this->assertSame($this->guidedImage->reveal(), $demand->getGuidedImage());
    }

    /**
     * @param $width
     * @param $height
     * @param null $returnObject
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getExistingImageDemand(
        $width,
        $height,
        $returnObject = null
    ) {
        return $this->getMockBuilder(ExistingImage::class)
            ->setConstructorArgs([$this->request->reveal(), $this->guidedImage->reveal(), $width, $height, $returnObject])
            ->getMockForAbstractClass();
    }
}

<?php

use ReliQArts\GuidedImage\GuidedImage;

/**
 * @coversDefaultClass \ReliQArts\GuidedImage\GuidedImage
 *
 * @internal
 */
final class GuidedImageTest extends \Orchestra\Testbench\TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCreate()
    {
        $guided = new GuidedImage();

        $this->assertNotNull($guided);
        $this->assertTrue(method_exists($guided, 'routeResized'));
    }
}

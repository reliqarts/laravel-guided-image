<?php

use ReliQArts\GuidedImage\GuidedImage;

class GuidedImageTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Test instance creation.
     */
    public function testCreate()
    {
        $guided = new GuidedImage();

        $this->assertNotNull($guided);
        $this->assertTrue(method_exists($guided, 'routeResized'));
    }
}

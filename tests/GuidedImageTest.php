<?php

namespace ReliqArts\GuidedImage\Tests;

use Orchestra\Testbench\TestCase;
use ReliqArts\GuidedImage\Exceptions\BadImplementation;
use ReliqArts\GuidedImage\Models\GuidedImage;

/**
 * @coversDefaultClass \ReliqArts\GuidedImage\Models\GuidedImage
 *
 * @internal
 */
final class GuidedImageTest extends TestCase
{
    /**
     * @covers ::__construct
     *
     * @throws BadImplementation
     */
    public function testCreate()
    {
        $guided = new GuidedImage();

        $this->assertNotNull($guided);
        $this->assertTrue(method_exists($guided, 'routeResized'));
    }
}

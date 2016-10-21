<?php

use ReliQArts\GuidedImage\GuidedImage;

class GuidedImageTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup app config variables.
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('guidedimage.routes.model', getenv('GUIDED_IMAGE_MODEL'));
    }

    /**
     * Test instance creation.
     */
    public function testCreate()
    {
        $guided = $this->createMock(GuidedImage::class);
        $guided->expects($this->any())->method('getClass')->will($this->returnValue(getenv('GUIDED_IMAGE_MODEL')));

        $this->assertNotNull($guided);
        $this->assertEquals($guided->getClass(), getenv('GUIDED_IMAGE_MODEL'));
    }
}

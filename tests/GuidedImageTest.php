<?php

use ReliQArts\GuidedImage\GuidedImage;
use ReliQArts\GuidedImage\Exceptions\ImplementationException;

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
    public function testInstance()
    {
        var_dump(get_declared_classes());
        die();
        $image = new GuidedImage();
        $this->expectOutputString($image->getClass());

        print getenv('GUIDED_IMAGE_MODEL');
    }
}

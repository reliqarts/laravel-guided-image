<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Demand;

use Illuminate\Http\Request;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\GuidedImage\Contract\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\TestCase as UnitTestCase;

abstract class TestCase extends UnitTestCase
{
    protected const DIMENSION = 200;

    /**
     * @var ObjectProphecy|Request
     */
    protected $request;

    /**
     * @var GuidedImage|ObjectProphecy
     */
    protected $guidedImage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->prophesize(Request::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
    }

    /**
     * @return array
     */
    public function nullValueProvider(): array
    {
        return [
            ['_'],
            ['n'],
            ['null'],
            [false],
            [null],
        ];
    }
}

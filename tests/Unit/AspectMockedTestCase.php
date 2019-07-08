<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit;

use AspectMock\Proxy\FuncProxy;
use AspectMock\Test;

abstract class AspectMockedTestCase extends TestCase
{
    private const GROUP = 'aspectMock';

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $parentNamespace;

    /**
     * @var FuncProxy
     */
    protected $abortFunc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->namespace = 'ReliqArts\\GuidedImage\\Services';
        $this->abortFunc = Test::func(
            $this->namespace,
            'abort',
            function (int $code = null) {
                return $code;
            }
        );

        $this->setGroups(array_merge($this->getGroups(), [self::GROUP]));
    }

    protected function tearDown(): void
    {
        Test::clean();

        parent::tearDown();
    }
}

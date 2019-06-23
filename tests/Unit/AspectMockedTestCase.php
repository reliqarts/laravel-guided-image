<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->setGroups(array_merge($this->getGroups(), [self::GROUP]));
    }

    protected function tearDown(): void
    {
        Test::clean();

        parent::tearDown();
    }
}

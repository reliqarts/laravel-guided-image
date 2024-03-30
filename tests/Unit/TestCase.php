<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use ReliqArts\GuidedImage\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use ProphecyTrait;

    private const GROUP = 'unit';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setGroups(array_merge($this->groups(), [self::GROUP]));
    }
}

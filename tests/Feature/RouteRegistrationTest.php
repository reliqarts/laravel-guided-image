<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReliqArts\GuidedImage\Tests\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class RouteRegistrationTest extends TestCase
{
    private const MODEL_NAME = 'guidedimage';

    private const RESIZE_ROUTE = self::MODEL_NAME . '.resize';

    private const THUMB_ROUTE = self::MODEL_NAME . '.thumb';

    public function testResizeRouteUriHasNoDoubleSlash(): void
    {
        $route = Route::getRoutes()->getByName(self::RESIZE_ROUTE);

        self::assertNotNull($route, 'Resize route must be registered.');
        self::assertStringNotContainsString('//', $route->uri());
    }

    public function testThumbRouteUriHasNoDoubleSlash(): void
    {
        $route = Route::getRoutes()->getByName(self::THUMB_ROUTE);

        self::assertNotNull($route, 'Thumb route must be registered.');
        self::assertStringNotContainsString('//', $route->uri());
    }

    public function testResizeRouteGeneratesCleanUrl(): void
    {
        $url = route(self::RESIZE_ROUTE, [self::MODEL_NAME => 1, 'width' => 800, 'height' => 600], false);

        self::assertStringNotContainsString('//', $url);
        self::assertStringContainsString('/.res/1/800-600', $url);
    }

    public function testThumbRouteGeneratesCleanUrl(): void
    {
        $url = route(self::THUMB_ROUTE, [self::MODEL_NAME => 1, 'method' => 'crop', 'width' => 100, 'height' => 100], false);

        self::assertStringNotContainsString('//', $url);
        self::assertStringContainsString('/.tmb/1/m.crop/100-100', $url);
    }
}

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use ReliqArts\GuidedImage\Contract\ConfigProvider;

/*
|--------------------------------------------------------------------------
| Guided Image Routes
|--------------------------------------------------------------------------
|
| This file defines the routes provided by the Guided Image Package.
|
*/

$configProvider = resolve(ConfigProvider::class);
$modelName = $configProvider->getGuidedModelName(true);

// get controllers for routes and create the routes for each
foreach ($configProvider->getControllersForRoutes() as $controllerName) {
    // if controller name's empty skip
    if (! $controllerName) {
        continue;
    }

    // if controller name doesn't contain namespace, add it
    if (! str_contains($controllerName, '\\')) {
        $controllerName = sprintf('App\\Http\\Controllers\\%s', $controllerName);
    }

    // the public route group
    Route::group(
        $configProvider->getRouteGroupBindings(),
        static function () use ($configProvider, $controllerName, $modelName) {
            // $guidedModel thumbnail
            Route::get(
                sprintf('.tmb/{%s}//m.{method}/{width}-{height}', $modelName),
                sprintf('%s@thumb', $controllerName)
            )->name(sprintf('%s.thumb', $modelName));

            // Resized $guidedModel
            Route::get(
                sprintf('.res/{%s}//{width}-{height}/{aspect?}/{upSize?}', $modelName),
                sprintf('%s@resized', $controllerName)
            )->name(sprintf('%s.resize', $modelName));

            // admin route group
            Route::group(
                $configProvider->getRouteGroupBindings([], 'admin'),
                static function () use ($controllerName, $modelName) {
                    // Used to empty directory photo cache (skimDir)
                    Route::get(
                        'empty-cache',
                        sprintf('%s@emptyCache', $controllerName)
                    )->name(sprintf('%s.empty-cache', $modelName));
                }
            );
        }
    );
}

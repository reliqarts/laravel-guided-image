<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;

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
    if (!$controllerName) {
        continue;
    }

    // if controller name doesn't contain namespace, add it
    if (false === strpos($controllerName, '\\')) {
        $controllerName = sprintf('App\\Http\\Controllers\\%s', $controllerName);
    }

    // the public route group
    Route::group(
        $configProvider->getRouteGroupBindings(),
        function () use ($configProvider, $controllerName, $modelName) {
            // $guidedModel thumbnail
            Route::get(
                sprintf('.tmb/{%s}//m.{method}/{width}-{height}/{returnObject?}', $modelName),
                [
                    'as' => sprintf('%s.thumb', $modelName),
                    'uses' => sprintf('%s@thumb', $controllerName),
                ]
            );

            // Resized $guidedModel
            Route::get(
                sprintf('.res/{%s}//{width}-{height}/{aspect?}/{upSize?}/{returnObject?}', $modelName),
                [
                    'as' => sprintf('%s.resize', $modelName),
                    'uses' => sprintf('%s@resized', $controllerName),
                ]
            );

            // Dummy $guidedModel
            Route::get(
                '.dum//{width}-{height}/{color?}/{fill?}/{returnObject?}',
                [
                    'as' => sprintf('%s.dummy', $modelName),
                    'uses' => sprintf('%s@dummy', $controllerName),
                ]
            );

            // admin route group
            Route::group(
                $configProvider->getRouteGroupBindings([], 'admin'),
                function () use ($controllerName, $modelName) {
                    // Used to empty directory photo cache (skimDir)
                    Route::get(
                        'empty-cache',
                        [
                            'as' => sprintf('%s.empty-cache', $modelName),
                            'uses' => sprintf('%s@emptyCache', $controllerName),
                        ]
                    );
                }
            );
        }
    );
}

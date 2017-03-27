<?php

use ReliQArts\GuidedImage\Helpers\RouteHelper;

/*
|--------------------------------------------------------------------------
| Guided Image Routes
|--------------------------------------------------------------------------
|
| This file defines the routes provided by the Guided Image Package.
|
*/

$guidedModel = RouteHelper::getRouteModel(true);

// get controllers for routes and create the routes for each
foreach (RouteHelper::getContollersForRoutes() as $guidedController) {

    // if controller name's empty skip
    if (! $guidedController) {
        continue;
    }

    // if controller name doesn't contain namespace, add it
    if (strpos($guidedController, '\\') === false) {
        $guidedController = "App\\Http\Controllers\\{$guidedController}";
    }

    // the public route group
    Route::group(RouteHelper::getRouteGroupBindings(), function () use ($guidedController, $guidedModel) {

        // $guidedModel thumbnail
        Route::get(".tmb/{{$guidedModel}}//m.{method}/{width}-{height}/{object?}", ['as' => "$guidedModel.thumb", 'uses' => "$guidedController@thumb"]);

        // Resized $guidedModel
        Route::get(".res/{{$guidedModel}}//{width}-{height}/{aspect?}/{upsize?}/{object?}", ['as' => "$guidedModel.resize", 'uses' => "$guidedController@resized"]);

        // Dummy $guidedModel
        Route::get(".dum/{width}-{height}/{color?}/{fill?}/{object?}", ['as' => "$guidedModel.dummy", 'uses' => "$guidedController@dummy"]);

        // admin route group
        Route::group(RouteHelper::getRouteGroupBindings([], 'admin'), function () use ($guidedController, $guidedModel) {
            // Used to empty directory photo cache (skimDir)
            Route::get('empty-cache', ['as' => "$guidedModel.empty-cache", 'uses' => "$guidedController@emptyCache"]);
        });
    });
}

<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    if (!$guidedController) {
        continue;
    }

    // if controller name doesn't contain namespace, add it
    if (strpos($guidedController, '\\') === false) {
        $guidedController = "App\\Http\Controllers\\{$guidedController}";
    }

    // the public route group
    Route::group(RouteHelper::getRouteGroupBindings(), function () use ($guidedController, $guidedModel) {

        // $guidedModel thumbnail
        Route::get(".thumb\{$guidedModel}\m.{method}\{width}-{height}\{object?}", ['as' => "$guidedModel.thumb", 'uses' => "$guidedController@thumb"]);

        // Resized $guidedModel
        Route::get(".{$guidedModel}\{$guidedModel}\{width}-{height}\{aspect?}\{upsize?}\{object?}", ['as' => "{$guidedModel}.resize", 'uses' => "$guidedController@resized"]);

        // Dummy $guidedModel
        Route::get(".dummy\{width}-{height}/{color?}/{fill?}/{object?}", ['as' => "{$guidedModel}.dummy", 'uses' => "$guidedController@dummy"]);

        // admin route group
        Route::group(RouteHelper::getRouteGroupBindings([], 'admin'), function () use ($guidedController, $guidedModel) {
            // Used to empty directory photo cache (skimDir)
            Route::get('empty-cache', ['as' => "{$guidedModel}.empty-cache", 'uses' => "$guidedController@emptyCache"]);
        });
    });
}

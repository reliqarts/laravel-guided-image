<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    // debug mode?
    'debug' => false,

    // Set the model to be guided.
    'model' => env('GUIDED_IMAGE_MODEL', 'Image'),

    // Set the guided model namespace.
    'model_namespace' => env('GUIDED_IMAGE_MODEL_NAMESPACE', "App\\"),
    
    // Where uploaded images should be stored. This is relative to the application's public directory.
    'upload_dir' => env('GUIDED_IMAGE_UPLOAD_DIR', 'uploads/images'),

    // Set the model to be guided.
    'database' => [
        // Guided image table.
        'image_table' => env('GUIDED_IMAGE_TABLE', 'images'),

        // Guided imageables table.
        'imageables_table' => env('GUIDED_IMAGEABLES_TABLE', 'imageables'),
    ],
    
    // Route related options.
    'routes' => [
        // Define controllers here which guided routes should be added onto:
        'controllers' => [
            env('GUIDED_IMAGE_CONTROLLER', 'ImageController'),
        ],

        // Set the prefix that should be used for routes
        'prefix' => env('GUIDED_IMAGE_ROUTE_PREFIX', 'image'),

        // Set the bindings for guided routes.
        'bindings' => [
            // public
            'public' => [
                'middleware' => 'web',
            ],

            // admin
            'admin' => [
                'middleware' => 'admin',
            ]
        ],

        // Route values to be treated as null.
        'nulls' => ['n', 'none', 'no', 'empty', 'false', 'auto', '_']
    ],

    // storage
    'storage' => [
        // Temporary storage directory for images already generated. 
        // This directory will live inside your application's storage directory.
        'skim_dir' => env('GUIDED_IMAGE_SKIM_DIR', 'images'),

        // Generated thumbnails will be temporarily kept inside this directory.
        'skim_thumbs' => env('GUIDED_IMAGE_SKIM_THUMBS', '.thumb'),

        // Generated resized images will be temporarily kept inside this directory.
        'skim_resized' => env('GUIDED_IMAGE_SKIM_RESIZED', '.resized'),
    ], 

    // headers
    'headers' => [
        // cache days
        'cache_days' => env('GUIDED_IMAGE_CACHE_DAYS', 2),

        // any aditional headers for guided images
        'additional'  => [],
    ],

];
<?php

return [
    // debug mode?
    'debug' => false,

    // Set the model to be guided.
    'model' => env('GUIDED_IMAGE_MODEL', 'Image'),

    // Set the guided model namespace.
    'model_namespace' => env('GUIDED_IMAGE_MODEL_NAMESPACE', 'App\\Models\\'),

    // Set the model to be guided.
    'database' => [
        // Guided image table.
        'image_table' => env('GUIDED_IMAGE_TABLE', 'images'),

        // Guided imageables table.
        'imageables_table' => env('GUIDED_IMAGEABLES_TABLE', 'imageables'),
    ],

    // image encoding @see: http://image.intervention.io/api/encode
    'encoding' => [
        'format' => env('GUIDED_IMAGE_ENCODING_FORMAT', 'png'),
        'quality' => env('GUIDED_IMAGE_ENCODING_QUALITY', 90),
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
                // 'middleware' => 'admin',
            ],
        ],
    ],

    // allowed extensions
    'allowed_extensions' => ['gif', 'jpg', 'jpeg', 'png'],

    // image rules for validation
    'rules' => 'required|mimes:png,gif,jpeg|max:2048',

    // storage
    'storage' => [
        // disk for in-built caching mechanism; MUST BE A LOCAL DISK, cloud disks such as s3 are not supported here.
        'cache_disk' => env('GUIDED_IMAGE_CACHE_DISK', 'local'),

        // upload disk
        'upload_disk' => env('GUIDED_IMAGE_UPLOAD_DISK', 'public'),

        // Where uploaded images should be stored. This is relative to the application's public directory.
        'upload_dir' => env('GUIDED_IMAGE_UPLOAD_DIR', 'uploads/images'),

        // generate upload sub directories (e.g. 2019/05)
        'generate_upload_date_sub_directories' => env('GUIDED_IMAGE_GENERATE_UPLOAD_DATE_SUB_DIRECTORIES', false),

        // Temporary storage directory for images already generated.
        // This directory will live inside your application's storage directory.
        'cache_dir' => env('GUIDED_IMAGE_CACHE_DIR', 'images'),

        // Generated thumbnails will be temporarily kept inside this directory.
        'cache_sub_dir_thumbs' => env('GUIDED_IMAGE_CACHE_SUB_DIR_THUMBS', '.thumb'),

        // Generated resized images will be temporarily kept inside this directory.
        'cache_sub_dir_resized' => env('GUIDED_IMAGE_CACHE_SUB_DIR_RESIZED', '.resized'),
    ],

    // headers
    'headers' => [
        // cache days
        'cache_days' => env('GUIDED_IMAGE_CACHE_DAYS', 366),

        // any additional headers for guided images
        'additional' => [],
    ],
];

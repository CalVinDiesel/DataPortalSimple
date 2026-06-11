<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portal Upload Limits
    |--------------------------------------------------------------------------
    |
    | Here you can easily configure the maximum allowed file sizes for the 
    | robotic folder scanner. These limits will automatically update 
    | both the backend validation rules and the frontend UI.
    |
    */
    'limits' => [
        'tileset_mb' => 50,
        'terrain_mb' => 10,
        'buildings_mb' => 500,
        'orthophoto_gb' => 10,
    ],
];

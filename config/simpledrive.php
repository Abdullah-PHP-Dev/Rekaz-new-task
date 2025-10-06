<?php
return [
    'default' => env('SIMPLEDRIVE_BACKEND', 'local'), // local | db | s3 | ftp
    'local' => [
        'path' => env('FILESYSTEM_DISK', storage_path('app/simpledrive')),
    ],
    's3' => [
        'endpoint' => env('SIMPLEDRIVE_S3_ENDPOINT', ''), // e.g. https://play.min.io
        'region' => env('SIMPLEDRIVE_S3_REGION', 'us-east-1'),
        'access_key' => env('SIMPLEDRIVE_S3_KEY', ''),
        'secret_key' => env('SIMPLEDRIVE_S3_SECRET', ''),
        'bucket' => env('SIMPLEDRIVE_S3_BUCKET', ''),
        // option to use path-style or virtual-host-style
        'path_style' => env('SIMPLEDRIVE_S3_PATH_STYLE', true),
    ],
    'ftp' => [
        'host' => env('SIMPLEDRIVE_FTP_HOST', ''),
        'username' => env('SIMPLEDRIVE_FTP_USER', ''),
        'password' => env('SIMPLEDRIVE_FTP_PASS', ''),
        'base_path' => env('SIMPLEDRIVE_FTP_BASE', '/'),
    ],
];

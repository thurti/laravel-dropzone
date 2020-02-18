<?php

return [
    'disk' => env('DROPZONE_DISK', 'local'),

    'upload_directory' => 'uploads',

    'min_filesize'  => 10,
    
    'max_filesize' => 15000, //in kb, is also the total limit for all files
    
    'max_filecount' => 5,
    
    'mime_types' => ['jpeg', 'png', 'pdf'],
    
    'input_name' => 'file',

    'input_name_delete' => 'file',

    'thumbnail' => true,

    'thumbnail_w' => 120,

    'thumbnail_h' => 120,

    'thumbnail_url_expires' => 30 //seconds until thumbnail url gets invalidated
];
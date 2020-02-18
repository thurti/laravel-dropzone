<?php

use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/dropzone/upload', 'NLGA\Dropzone\Http\Controllers\DropzoneController@upload');
    Route::get('/dropzone/files', 'NLGA\Dropzone\Http\Controllers\DropzoneController@files');
    Route::post('/dropzone/delete', 'NLGA\Dropzone\Http\Controllers\DropzoneController@delete');
    Route::get('/dropzone/thumbnail/{dir}/{uuid}/{file}', 'NLGA\Dropzone\Http\Controllers\DropzoneController@thumbnail')->name('thumbnail');
});
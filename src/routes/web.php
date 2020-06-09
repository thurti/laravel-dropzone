<?php

use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/dropzone/upload', 'NLGA\Dropzone\Http\Controllers\DropzoneController@upload')->name('dropzone.upload');
    Route::get('/dropzone/files', 'NLGA\Dropzone\Http\Controllers\DropzoneController@files')->name('dropzone.files');
    Route::post('/dropzone/delete', 'NLGA\Dropzone\Http\Controllers\DropzoneController@delete')->name('dropzone.delete');
    Route::get('/dropzone/thumbnail/{dir}/{uuid}/{file}', 'NLGA\Dropzone\Http\Controllers\DropzoneController@thumbnail')->name('dropzone.thumbnail');
});
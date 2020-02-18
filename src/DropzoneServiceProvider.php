<?php
namespace NLGA\Dropzone;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class DropzoneServiceProvider extends ServiceProvider
{
    public function boot()
    {    
        //make config publishable
        $this->publishes([__DIR__.'/config/dropzone.php' => config_path('dropzone.php')], 'config');
        $this->publishes([__DIR__.'/resources/lang' => resource_path('lang/vendor/dropzone')], 'lang');

        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'dropzone');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    public function register()
    {
        //default package config
        $this->mergeConfigFrom(
            __DIR__.'/config/dropzone.php',  'dropzone'
        );
        
        //register InterventionImage lib
        $this->app->register('Intervention\Image\ImageServiceProvider');

        //create dropzone instance
        //uses closure otherwise Session won't work
        $this->app->singleton('NLGA\Dropzone\Dropzone', function () {
            //create unique temporary upload directory
            $uuid = Session::get('uuid') ?? Str::uuid();
            $upload_directory = config('dropzone.upload_directory') . '/' . $uuid;
            Session::put('uuid', $uuid);

            return new Dropzone(config('dropzone.disk'), $upload_directory);            
        });
    }
}

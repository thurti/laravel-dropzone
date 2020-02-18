<?php
namespace NLGA\Dropzone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method void setUploadDirectory(string $path)
 * @method bool store(\Illuminate\Http\File[] $files)
 * @method array getFiles(bool $inlcude_path = true)
 * @method bool delete(string $file)
 * @method bool clean()
 * @method int getTotalSize()
 * @method int getCount()
 * 
 * @see NLGA\Dropzone\Dropzone
 */
class Dropzone extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'NLGA\Dropzone\Dropzone';
    }
}

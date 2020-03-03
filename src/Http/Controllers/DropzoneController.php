<?php
namespace NLGA\Dropzone\Http\Controllers;

use NLGA\Dropzone\Http\Requests\UploadRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use NLGA\Dropzone\Facades\Dropzone;
use Intervention\Image\Facades\Image as Image;

class DropzoneController extends Controller 
{
    use AuthorizesRequests, ValidatesRequests;
    
    public function __construct()
    {
        $this->middleware(config('dropzone.middleware'));
    }

    public function upload(UploadRequest $request)
    {
        $file = $request->file(config('dropzone.input_name'));
        $file = ($file && !is_array($file)) ? [$file] : $file;

        if ($file && Dropzone::store($file)) {
            return response()->json(__('dropzone::messages.upload.success'), 200);
        } else {
            return response()->json(__('dropzone::messages.upload.fail'), 500);
        }
    }

    public function files()
    {
        $files = Dropzone::getFiles(false);
        return response()->json($files);
    }

    public function delete(Request $request)
    {
        $file = $request->input(config('dropzone.input_name_delete'));

        if ($file && Dropzone::delete($file)) {
            return response()->json(__('dropzone::messages.delete.success'), 200);
        } else {
            return response()->json(__('dropzone::messages.delete.fail'), 500);
        }
    }

    public function thumbnail($dir, $uuid, $file, Request $request)
    {
        if (config('dropzone.thumbnail') !== true) {
            abort(404);
        }
        
        if (!$request->hasValidSignature()) { //only works with URL::temporarySignedRoute()
            abort(403);
        }
        
        try {
            $path = sprintf('%s/%s/%s', $dir, $uuid, $file);
            $file = Storage::disk(config('dropzone.disk'))->get($path);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $th) {
            $file = false;
        }

        if ($file !== false) {
            try {
                $image = Image::make($file)->fit(config('dropzone.thumbnail_w'), config('dropzone.thumbnail_h'));
                return $image->response('jpg', config('dropzone.thumbnail_q'));
            } catch (\Intervention\Image\Exception\NotReadableException $e) {
                return response()->json(__('dropzone::messages.thumbnail.no_image'), 406);
            }
        } else {
            return response()->json(__('dropzone::messages.thumbnail.not_found'), 404);
        }
    }
}
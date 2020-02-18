<?php

namespace NLGA\Dropzone\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use NLGA\Dropzone\Facades\Dropzone;
use NLGA\Dropzone\Rules\TotalFileCount;
use NLGA\Dropzone\Rules\TotalFileSize;

class UploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $input_name = config('dropzone.input_name');
        $min_filesize = config('dropzone.min_filesize');
        $max_filesize = config('dropzone.max_filesize');
        $max_filecount = config('dropzone.max_filecount');
        $mimes = implode(',', config('dropzone.mime_types'));
        
        $current_size = Dropzone::getTotalSize();
        $current_count = Dropzone::getCount();

        if (is_array($this->file($input_name))) { //multiple files
            return [
                $input_name => [
                    'array', 
                    new TotalFileCount($max_filecount, $current_count), 
                    new TotalFileSize($max_filesize, $current_size)
                ],
                $input_name . '.*' => [
                    'file', 
                    'min:' . $min_filesize, 
                    'max:' . $max_filesize, 
                    'mimes:' . $mimes
                ]
            ];
        } else { //single file upload
            return [
                $input_name => [
                    'file', 
                    'min:' . $min_filesize, 
                    'max:' . $max_filesize, 
                    new TotalFileCount($max_filecount, $current_count), 
                    new TotalFileSize($max_filesize, $current_size),
                    'mimes:' . $mimes
                ]
            ];
        }
    }
}

<?php
namespace NLGA\Dropzone\Tests\Http\Requests;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use NLGA\Dropzone\Facades\Dropzone;
use NLGA\Dropzone\Http\Requests\UploadRequest;
use NLGA\Dropzone\Test\TestCase;
use Mockery;

class UploadRequestTest extends TestCase
{
    use WithoutMiddleware;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('dropzone.max_filesize', 1000);
        Config::set('dropzone.mime_types', ['jpeg', 'png', 'pdf']);
        Config::set('dropzone.max_filecount', 2);

        $uploadRequest = Mockery::mock('NLGA\Dropzone\Http\Requests\UploadRequest')->makePartial();
        $uploadRequest->shouldReceive('file')->andReturn([]); //return array of files
        $this->rules = $uploadRequest->rules();
    }

    public function test_passes_on_valid_input()
    {
        $file = UploadedFile::fake()->create('test.pdf', 600);
        $files = [$file];

        $validator = Validator::make(['file' => $files], $this->rules);
        $passes = $validator->passes();

        $this->assertTrue($passes);
    }

    public function test_fails_on_file_size_error()
    {
        $file = UploadedFile::fake()->create('test.pdf', 600);
        $files = [$file, $file];
        
        $validator = Validator::make(['file' => $files], $this->rules);
        $failed = $validator->fails();

        $this->assertTrue($failed);
    }

    public function test_fails_on_file_size_error_with_already_uploaded_files()
    {
        $file = UploadedFile::fake()->create('test.pdf', 400);
        $files = [$file, $file];
        
        Dropzone::shouldReceive('getCount')->once()->andReturn(0);
        Dropzone::shouldReceive('getTotalSize')->once()->andReturn(600);

        $rules = (new UploadRequest())->rules();
        $validator = Validator::make(['file' => $files], $rules);
        $failed = $validator->fails();

        $this->assertTrue($failed);
    }

    public function test_fails_on_file_count_error()
    {
        $file = UploadedFile::fake()->create('test.pdf', 10);
        $files = [$file, $file, $file];

        $validator = Validator::make(['file' => $files], $this->rules);
        $failed = $validator->fails();

        $this->assertTrue($failed);
    }

    public function test_fails_on_file_count_with_already_uploaded_files()
    {
        $file = UploadedFile::fake()->create('test.pdf', 10);
        $files = [$file, $file];

        Dropzone::shouldReceive('getTotalSize')->once()->andReturn(0);
        Dropzone::shouldReceive('getCount')->once()->andReturn(1);

        $rules = (new UploadRequest())->rules();
        $validator = Validator::make(['file' => $files], $rules);
        $failed = $validator->fails();

        $this->assertTrue($failed);
    }

    public function test_fails_on_mime_error()
    {
        $files = [
            UploadedFile::fake()->create('test.jpg', 10), 
            UploadedFile::fake()->create('test.bmp', 10)
        ];

        $validator = Validator::make(['file' => $files], $this->rules);
        $failed = $validator->fails();

        $this->assertTrue($failed);
    }

}
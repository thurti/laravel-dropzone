<?php
namespace NLGA\Dropzone\Tests\Http\Controllers;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Mockery;
use NLGA\Dropzone\Facades\Dropzone;
use NLGA\Dropzone\Test\TestCase;
use Intervention\Image\Facades\Image;
use NLGA\Dropzone\Http\Controllers\DropzoneController;

class DropzoneControllerTest extends TestCase
{
    use WithoutMiddleware;

    public function test_upload_files()
    {
        $files = [UploadedFile::fake()->create('test.jpg', 100)];
        
        Dropzone::shouldReceive('getCount')->once()->andReturn(0);
        Dropzone::shouldReceive('getTotalSize')->once()->andReturn(0);
        Dropzone::shouldReceive('store')->once()->with($files)->andReturn(true);

        $response = $this->postJson('/dropzone/upload', ['file' => $files]);
        $response->assertOk();
    }

    public function test_upload_single_file()
    {
        $file = UploadedFile::fake()->create('test.jpg', 100);
        
        Dropzone::shouldReceive('getCount')->once()->andReturn(0);
        Dropzone::shouldReceive('getTotalSize')->once()->andReturn(0);
        Dropzone::shouldReceive('store')->once()->with([$file])->andReturn(true);

        $response = $this->postJson('/dropzone/upload', ['file' => $file]);
        $response->assertOk();
    }

    public function test_returns_500_on_upload_failed()
    {
        $files = [UploadedFile::fake()->create('test.jpg', 100)];

        Dropzone::shouldReceive('getCount')->once()->andReturn(0);
        Dropzone::shouldReceive('getTotalSize')->once()->andReturn(0);
        Dropzone::shouldReceive('store')->once()->andReturn(false);

        $response = $this->postJson('/dropzone/upload', ['file' => $files]);
        $response->assertStatus(500);
    }

    public function test_returns_500_on_empty_upload()
    {
        Dropzone::shouldReceive('store')->never();

        $response = $this->postJson('/dropzone/upload', []);
        $response->assertStatus(500);
    }

    public function test_get_files()
    {
        $files = [
            ['name' => 'test1.pdf', 'size' => 100],
            ['name' => 'test2.pdf', 'size' => 100]
        ];
        
        Dropzone::shouldReceive('getFiles')->once()->andReturn($files);
        $response = $this->getJson('/dropzone/files');

        $response->assertJson([
            ['name' => 'test1.pdf', 'size' => 100],
            ['name' => 'test2.pdf', 'size' => 100]
        ]);
    }
    
    public function test_delete_file()
    {
        Dropzone::shouldReceive('delete')->once()->with('test1.jpg')->andReturn(true);

        $result = $this->postJson('/dropzone/delete', ['file' => 'test1.jpg']);

        $result->assertOk();
    }
    
    public function test_delete_returns_500_on_fail()
    {
        Dropzone::shouldReceive('delete')->andReturn(false);

        $result = $this->postJson('/dropzone/delete');

        $result->assertStatus(500);
    }

    public function test_get_thumbnail_fails_with_invalid_link()
    {
        $result = $this->get('/dropzone/thumbnail/uploads/uuid/test1.jpg');
        $result->assertStatus(403);
    }

    public function test_get_thumbnail_with_valid_link_file_not_found()
    {
        $result = $this->get(URL::temporarySignedRoute('thumbnail', now()->addSeconds(30), ['uploads', 'uuid', 'test1.jpg']));
        $result->assertStatus(404);
    }

    public function test_config_thumbnail_disabled_returns_404()
    {
        Config::set('dropzone.thumbnail', false);

        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('download')->andReturn('');

        $result = $this->get(URL::temporarySignedRoute('thumbnail', now()->addSeconds(30), ['uploads', 'uuid', 'test1.jpg']));
        $result->assertStatus(404);
    }

    public function test_creates_thumbnail()
    {
        Config::set('dropzone.thumbnail_w', 100);
        Config::set('dropzone.thumbnail_h', 100);
        
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('get')->andReturn('');
       
        Image::shouldReceive('make')->once()->andReturn(Mockery::self());
        Image::shouldReceive('fit')->withArgs([100,100])->andReturn(Mockery::self());
        Image::shouldReceive('response')->andReturn('');

        $result = $this->get(URL::temporarySignedRoute('thumbnail', now()->addSeconds(30), ['uploads', 'uuid', 'test1.jpg']));
        $result->assertStatus(200);
    }

    public function test_thumbnail_returns_error_if_not_an_image()
    { 
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('get')->andReturn(true); //return true instead of image string, should trigger error
       
        $result = $this->get(URL::temporarySignedRoute('thumbnail', now()->addSeconds(30), ['uploads', 'uuid', 'test1.jpg']));
        $result->assertStatus(406);
    }

    public function test_adds_middleware_from_config()
    {
        Config::set('dropzone.middleware', ['web', 'auth']);
        $controller = new DropzoneController();
        $middlewares = $controller->getMiddleware();
        $this->assertEquals('web', $middlewares[0]['middleware']);
        $this->assertEquals('auth', $middlewares[1]['middleware']);
    }
}

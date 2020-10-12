<?php
namespace NLGA\Dropzone\Test;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Mockery;
use NLGA\Dropzone\Dropzone;

class DropzoneTest extends TestCase
{
    use InteractsWithSession;
    
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->dropzone = new Dropzone('local');
        $this->dropzone->setUploadDirectory('uploads/session');
    }
    
    public function test_creates_instance()
    {
        $this->assertInstanceOf('NLGA\Dropzone\Dropzone', $this->dropzone);
    }

    public function test_add_filenames()
    {
        $dropzone = new Dropzone('local');

        $dropzone->addFilenames(['hash1' => 'file1', 'hash2' => 'file2']);
        $file = $dropzone->getHashFromSessionByFilename('file2');

        $this->assertEquals(Session::get('hash2'), 'file2');
        $this->assertEquals('hash2', $file);
    }

    public function test_stores_files()
    {
        $file1 = UploadedFile::fake()->create('test1.jpg', 10);
        $file2 = UploadedFile::fake()->create('test2.jpg', 20);
     
        $hash1 = pathinfo($file1->hashName(), PATHINFO_FILENAME);
        $hash2 = pathinfo($file2->hashName(), PATHINFO_FILENAME);
        $path1 = 'uploads/session/' . $hash1;
        $path2 = 'uploads/session/' . $hash2;

        Session::shouldReceive('put')->once()->withArgs([$hash1, 'test1.jpg']);
        Session::shouldReceive('put')->once()->withArgs([$hash2, 'test2.jpg']);
        $result = $this->dropzone->store([$file1, $file2]);

        
        Storage::assertExists([$path1, $path2]);
        $this->assertTrue($result);
    }

    public function test_returns_hash_from_session_by_filename()
    {
        Session::shouldReceive('all')->andReturn(['hash1' => 'test1.jpg', 'hash2' => 'test2.jpg']);

        $result = $this->dropzone->getHashFromSessionByFilename('test2.jpg');

        $this->assertEquals('hash2',  $result);
    }

    public function test_returns_files_array()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('files')->with('uploads/session')->andReturn(['uploads/session/hash1', 'uploads/session/hash2']);
        Storage::shouldReceive('size')->andReturns(10, 20);
        
        Session::shouldReceive('get')->with('hash1')->andReturns('test1.jpg');
        Session::shouldReceive('get')->with('hash2')->andReturns('test2.jpg');

        URL::shouldReceive('temporarySignedRoute')->withArgs([
            'dropzone.thumbnail', 
            Mockery::type('Illuminate\Support\Carbon'), 
            ['dir' => 'uploads', 'uuid' =>'session', 'file' => 'hash1']
        ])->andReturn('thumb1.jpg');
        
        URL::shouldReceive('temporarySignedRoute')->withArgs([
            'dropzone.thumbnail', 
            Mockery::type('Illuminate\Support\Carbon'), 
            ['dir' => 'uploads', 'uuid' =>'session', 'file' => 'hash2']
        ])->andReturn('thumb2.jpg');

        $files = $this->dropzone->getFiles();

        $this->assertEquals([
            ['name' => 'test1.jpg', 'hash' => 'hash1', 'size' => 10, 'path' => 'uploads/session/hash1', 'thumbnail' => 'thumb1.jpg'],
            ['name' => 'test2.jpg', 'hash' => 'hash2', 'size' => 20, 'path' => 'uploads/session/hash2', 'thumbnail' => 'thumb2.jpg']
        ], $files);
    }

    public function test_delete_file_by_hash()
    {
        $dropzone = Mockery::mock('\NLGA\Dropzone\Dropzone', ['local', 'uploads/session'])->makePartial();
        Session::shouldReceive('forget')->once()->with('hash1');
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('delete')->once()->with('uploads/session/hash1')->andReturn(true);
        
        $result = $dropzone->delete('hash1');

        $this->assertTrue($result);
    }

    public function test_clean_files()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('deleteDirectory')->once()->with('uploads/session')->andReturn(true);
        Session::shouldReceive('flush')->once();

        $result = $this->dropzone->clean();
        
        $this->assertTrue($result);
    }

    public function test_clean_files_and_preserves_session()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('deleteDirectory')->once()->with('uploads/session')->andReturn(true);
        Session::shouldReceive('flush')->never();

        $result = $this->dropzone->clean(false);
        
        $this->assertTrue($result);
    }

    public function test_get_total_size()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('files')->once()->with('uploads/session')->andReturn(['hash1.jpg', 'hash2.jpg']);
        Storage::shouldReceive('size')->with('hash1.jpg')->andReturn(600); //in byte
        Storage::shouldReceive('size')->with('hash2.jpg')->andReturn(400);
        
        $size = $this->dropzone->getTotalSize(); //in kb

        $this->assertEquals($size, 1);
    }

    public function test_get_total_size_returns_0_if_none()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('files')->once()->with('uploads/session')->andReturn([]);
        
        $size = $this->dropzone->getTotalSize();

        $this->assertEquals($size, 0);
    }

    public function test_count()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('files')->once()->with('uploads/session')->andReturn(['hash1.jpg', 'hash2.jpg', 'hash3.jpg']);

        $count = $this->dropzone->getCount();

        $this->assertEquals(3, $count);
    }

    public function test_count_returns_0_if_none()
    {
        Storage::shouldReceive('disk')->andReturn(Mockery::self());
        Storage::shouldReceive('files')->once()->with('uploads/session')->andReturn([]);

        $count = $this->dropzone->getCount();

        $this->assertEquals(0, $count);
    }
}

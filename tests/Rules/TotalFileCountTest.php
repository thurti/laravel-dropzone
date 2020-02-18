<?php

namespace NLGA\Dropzone\Test\Rules;

use Illuminate\Http\UploadedFile;
use NLGA\Dropzone\Rules\TotalFileCount;
use NLGA\Dropzone\Test\TestCase;

class TotalFileCountTest extends TestCase
{
    public function test_pass_on_valid_count()
    {
        $rule = new TotalFileCount(3);
        $files = [
            UploadedFile::fake()->create('test1', 10),
            UploadedFile::fake()->create('test2', 10),
            UploadedFile::fake()->create('test3', 10),
        ];
        
        $is_valid = $rule->passes('files', $files);

        $this->assertTrue($is_valid);
    }

    public function test_fails_on_invalid_count()
    {
        $rule = new TotalFileCount(2);
        $files = [
            UploadedFile::fake()->create('test1', 10),
            UploadedFile::fake()->create('test2', 10),
            UploadedFile::fake()->create('test3', 81),
        ];

        $is_valid = $rule->passes('files', $files);
        
        $this->assertFalse($is_valid);
    }

    public function test_new_file_plus_existing_files_exceeding_total_limit()
    {
        $rule = new TotalFileCount(2, 1);
        $files = [
            UploadedFile::fake()->create('test1', 40),
            UploadedFile::fake()->create('test2', 20),
        ];

        $is_valid = $rule->passes('files', $files);
        
        $this->assertFalse($is_valid);
    }

    public function test_works_with_single_file()
    {
        $rule = new TotalFileCount(2, 1);
        $files = UploadedFile::fake()->create('test1', 40);

        $is_valid = $rule->passes('files', $files);
        $this->assertTrue($is_valid);
    }
}

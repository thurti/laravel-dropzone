<?php
namespace NLGA\Dropzone\Test;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return ['NLGA\Dropzone\DropzoneServiceProvider'];
    }
}

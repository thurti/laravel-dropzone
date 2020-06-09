<?php
namespace NLGA\Dropzone;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Dropzone
{
    /**
     * Storage disk name.
     *
     * @var string
     */
    protected $disk;

    /**
     * Path to upload directory.
     *
     * @var string
     */
    protected $upload_directory;


    /**
     * @param string $disk
     * @param string $upload_directory
     */
    public function __construct(string $disk, string $upload_directory = '') 
    {
        $this->disk             = $disk;
        $this->upload_directory = $upload_directory;
    }
    
    /**
     * Set upload directory.
     * 
     * @param string $path
     * @return void
     */
    public function setUploadDirectory(string $path): void
    {
        $this->upload_directory = $path;
    }

    /**
     * Adds [hash => filename] to session so that Dropzone can receive them.
     * Useful for prepopulating dropzone with files.
     *
     * @param array $hash_filename_map
     * @return void
     */
    public function addFilenames(array $hash_filename_map): void
    {
        foreach ($hash_filename_map as $hash => $filename) {
            Session::put($hash, $filename);
        }        
    }

    
    /**
     * Store files to $upload_directory and puts [hash => filename] in session.
     *
     * @param array $files Illuminate\Http\File or Illuminate\Http\UploadedFile
     * @return boolean 
     */
    public function store(array $files): bool
    {
        $success = true;

        foreach($files as $file) {
            $hashname = pathinfo($file->hashName(), PATHINFO_FILENAME); //filename hash without file ending
            $path = Storage::disk($this->disk)->putFileAs($this->upload_directory, $file, $hashname, 'private');
            
            if ($path !== false) {
                Session::put($hashname, $file->getClientOriginalName());
            } else {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Returns array of files in upload_directory.
     *
     * @param boolean $include_path Include real file path, else null.
     * @return array  ['name' => 'file.jpg, 'size' => 100, 'path' => 'uploads/hashName.jpg']
     */
    public function getFiles(bool $include_path = true): array
    {
        $files = Storage::disk($this->disk)->files($this->upload_directory);

        return array_map(function ($file) use ($include_path) {
            $hashname = basename($file);
            $params = explode('/', $file);
            $thumbnail = URL::temporarySignedRoute('thumbnail', now()->addSeconds(config('thumbnail_url_expires')), [
                'dir'  => $params[0],
                'uuid' => $params[1],
                'file' => $params[2]
            ]);

            return [
                'name' => Session::get($hashname),
                'hash' => $hashname,
                'size' => Storage::size($file),
                'path' => ($include_path) ? $file : null,
                'thumbnail' => (config('dropzone.thumbnail') === true) ? $thumbnail : null
            ];
        }, $files);
    }

    /**
     * Delete $hash.
     *
     * @param string $hash
     * @return boolean
     */
    public function delete(string $hash): bool
    {
        Session::forget($hash);
        return Storage::disk($this->disk)->delete($this->upload_directory . '/' . $hash);
    }

    /**
     * Searches session for filename and returns stored hash.
     *
     * @param string $file
     * @return string       Hashname of file.
     */
    public function getHashFromSessionByFilename(string $file): string
    {
        $filename = basename($file);
        return array_search($filename, Session::all(), true);
    }

    /**
     * Deletes upload_directory.
     *
     * @param bool $flushSession
     * @return boolean
     */
    public function clean($flushSession = true): bool
    {
        if ($flushSession === true) {
            Session::flush();
        }

        return Storage::disk($this->disk)->deleteDirectory($this->upload_directory);
    }

    /**
     * Returns total size in kb of files currently exist in upload directory.
     *
     * @return int
     */
    public function getTotalSize(): int
    {
        $files = Storage::disk($this->disk)->files($this->upload_directory);

        return array_reduce($files, function ($total, $file) {
            return $total += Storage::disk($this->disk)->size($file);
        }, 0) / 1000;
    }

    /**
     * Returns total count of files currently exist in upload directory.
     *
     * @return int
     */
    public function getCount(): int
    {
        $files = Storage::disk($this->disk)->files($this->upload_directory);
        return count($files) ?? 0;
    }
}

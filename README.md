# Laravel Dropzone

laravel-dropzone is a simple backend for uploading files via ajax (like [Dropzone.js](https://dropzonejs.com)). It stores the files in a temporary folder named by uuid per session.

## Installation

```
composer require nlga/laravel-dropzone
php artisan vendor:publish --provider="NLGA\Dropzone\DropzoneServiceProvider" --tag=config --tag=lang
```

## Config
```php
return [
    'disk' => env('DROPZONE_DISK', 'local'),

    'upload_directory' => 'uploads', //base upload directory

    'middleware' => ['web', 'auth'], //middleware for all routes

    'min_filesize'  => 10, //KB
    
    'max_filesize' => 15000, //KB, is also the total limit for all files
    
    'max_filecount' => 5,
    
    'mime_types' => ['jpeg', 'png', 'pdf'], //file ending or mimes
    
    'input_name' => 'file', //POST parameter name

    'input_name_delete' => 'file', //POST parameter name for deleting file by hashname

    'thumbnail' => true, //enable thumbnail route

    'thumbnail_w' => 120, //combination of resize and crop to fit image size

    'thumbnail_h' => 120, //combination of resize and crop to fit image size

    'thumbnail_q' => 60, //thumbnail jpeg quality

    'thumbnail_url_expires' => 30 //seconds until thumbnail url gets invalidated
];
```


## Usage
larave-dropzone adds 4 routes which you can use with your js upload script. By default, the routes use the `web` and `auth`  middleware. Middleware can be changed in `config/dropzone.php`.

```
//Routes
POST: dropzone/upload                         //only works with files array as input, eg: <input type="file" name="files[]" />
GET: dropzone/files                           //returns json array ['name', 'size', 'thumbnail'] with uploaded files
POST: dropzone/delete                         //delete file with name `name="{config.dropzon.input_name_delete}"`

GET: /dropzone/thumbnail/{dir}/{uuid}/{file}  //temporary url for preview image only valid for 30 seconds
```

In laravel you can use the `NLGA\Dropzone\Facades\Dropzone` facade to get access to the uploaded files. You should move the uploaded files to a permanent location and call `Dropzone::clean()` afterwards to delete all temporary files. The path to the temporary directory is stored in the session, so it will be lost when the session gets invalidated.

```php
Dropzone::store(\Illuminate\Http\File[] $files)
Dropzone::getFiles(bool $inlcude_path = true)
Dropzone::delete(string $file)
Dropzone::clean()
Dropzone::getTotalSize()
Dropzone::getCount()

Dropzone::setUploadDirectory(string $upload_directory) //set custom upload directory
Dropzone::addFilenames(array $hash_filename_map) // ['hash' => 'filename', ...] should match files in $upload_directory
```

### Custom Upload Directory
You can also change the upload directory to a permanent location by calling `Dropzone::setUploadDirectory('new/path')` so you don't have to deal with the temporary directory.

If you want to prepopulate previously uploaded files, you can use `Dropzone::setUploadDirectory('new/path')` together with `Dropzone::addFilenames(['hash' => 'filename'])` with `hash` as the filename in the upload directory and `filename` as the real file name.

## Example
This is an example with (like [Dropzone.js](https://dropzonejs.com)). But any other js uploader should do as long as you use the defined routes.

### Blade Template
```html
<!-- blade template -->


<!-- insert dropzone.js in head section -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.0/dropzone.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.0/min/dropzone.min.js"></script>


<!-- in your body -->
<form action="/dropzone/upload" class="dropzone">
  @csrf
  <div class="fallback">
    <input name="file" type="file" multiple />
  </div>
</form>
```

### Controller

```php
//somewhere in your app (eg. controller), after you've uploaded some files

use NLGA\Dropzone\Facades\Dropzone;

public function saveFiles() {
  $files = Dropzone::getFiles(); //get array with uploaded files
  
  //do something with the uploaded files, eg. store
  foreach($files as $file) {
    Storage::copy($file['path'], 'new/path/' + $file['name']);
  }

  //clean temporary upload directory
  Dropzone::clean();
}
```

## Advanced Usage
More advanced example with validation, delete files and preload files.

### blade template
```html
<!-- blade template -->
<div id="file_upload" class="dropzone">
  <div class="fallback">
    <input name="file" type="file" multiple />
  </div>
</div>

<script>
(function () {
  const upload = 'dropzone/upload';
  const files  = 'dropzone/files';
  const del = 'dropzone/delete';
  const token = '{{ csrf_token() }}';
  
  Dropzone.autoDiscover = false;
  
  const dropzone = new Dropzone('div#file_upload', {
    url: upload,
    params: {
        _token: token,
        credentials: 'same-origin'
    },
    uploadMultiple: true,
    paramName: "{{ config('dropzone.input_name') }}",
    maxFilesize: {{ config('dropzone.max_filesize') }},
    maxFiles: {{ config('dropzone.max_filecount') }},
    acceptedFiles: "{{ implode(',.', config('dropzone.mime_types')) }}",
    addRemoveLinks: true,

    init: async function () {

      /**
       * log errors
       **/
      this.on('error', function (error) {
        console.log(error);
      });

      /**
       * set image icon for pdf files, use thumbnail url otherwise
       **/
      this.on('addedfile', function (file) {
        if (file.name.match(/.*\.pdf$/)) {
          this.emit("thumbnail", file, '{{ asset('img/pdf.png') }}');
        } else {
          this.emit('thumbnail', file, file.thumbnail);
        }
      });

      /**
       * delete file from server
       **/
      this.on('removedfile', async function (file) {
        if (file.status !== 'error') { //only delete successfuly uploaded files
          const data = new FormData();
          data.append('_token', token);
          data.append('{{ config("dropzone.input_name_delete") }}', file.hash);

          let response;
          try{
            response = await fetch(del, {
              credentials: 'same-origin',
              method     : 'POST',
              body       : data
            });
          } catch(e) {
            this.emit('error', e);
          }

          if (response && response.ok) {
            this.options.maxFiles = this.options.maxFiles + 1;
          } else {
            this.emit('error', response);
          }
        
        }
      }); 

      /**
      * Preload Files
      **/
      let response,
      json;
      try{
        response = await fetch(files, {
          credentials: 'same-origin',
          method: 'GET'
        });
        json = await response.json();
      } catch(e) {
        this.emit('error', e);
      }

      if (response && response.ok) {
        json.forEach((file) => {
          this.emit('addedfile', file);
          this.emit('complete', file);
          this.options.maxFiles = this.options.maxFiles -1;
        });
      } else {
        this.emit('error', response);
      }  
    }
  });
})()
</script>

```

## Tests
```
phpunit
//or 
composer run-script test --timeout=0 //watcher
```
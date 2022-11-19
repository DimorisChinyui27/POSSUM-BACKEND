<?php


namespace App\Services;


use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\File;

class FileService
{
    use FileUploadTrait;

    private string $folder;

    public function __construct($folder = 'questions')
    {
        $this->folder = $folder;
    }

    public function storeFiles($files, $model) {
        $i = 0;
        foreach ($files as $file) {
            $size = $file->getSize();
            $type = $file->getMimeType();
            $model->media()->create([
                'file_name' => $this->uploadImages($model->name, $file, $i, $this->folder, 540, 720),
                'file_size' => $size,
                'file_type' => $type,
                'file_status' => true,
                'file_sort' => $i
            ]);
        }
    }

    public function unlinkFile($file, $folderName='questions')
    {
        if (File::exists('storage/files/'. $folderName .'/' . $file)) {
            unlink('storage/files/'. $folderName .'/' . $file);
        }
    }

}

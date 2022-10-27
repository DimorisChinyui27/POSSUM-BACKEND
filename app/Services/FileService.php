<?php


namespace App\Services;


use App\Traits\FileUploadTrait;

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
                'file_name' => $this->uploadImages($model->name, $model, $i, $this->folder, 540, 720),
                'file_size' => $size,
                'file_type' => $type,
                'file_status' => true,
                'file_sort' => $i
            ]);
        }
    }

}

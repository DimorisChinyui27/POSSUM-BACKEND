<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

trait FileUploadTrait
{
    protected $path = 'app/public/files/';

    public function uploadImage($name, $img, $folderName, $image_width = NULL, $image_height = NULL): string
    {
        File::isDirectory(storage_path($this->path . $folderName) or
            File::makeDirectory(storage_path($this->path . $folderName), 0777, true, true));
        $image_name = $this->imageName($name, $img);

        Image::make($img->getRealPath())->resize($image_width, $image_height, function ($constraint) {
            $constraint->aspectRatio();
        })->save(storage_path($this->path . $folderName . '/' . $image_name), 100);

        return $image_name;
    }

    protected function imageName($imageName, $image): string
    {
        return Str::slug($imageName) . '.' . $image->getClientOriginalExtension();
    }

    public function uploadImages($name, $img, $i, $folderName, $image_width = NULL, $image_height = NULL): string
    {
        File::isDirectory(storage_path($this->path . $folderName) or
            File::makeDirectory(storage_path($this->path . $folderName), 0777, true, true));

        $image_name = $this->randomImageName($name, $img, $i);
        try {
            Image::make($img->getRealPath())
                ->resize($image_width, $image_height, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(storage_path($this->path . $folderName . '/' . $image_name), 100);
        } catch (\Exception $exception) {
            return $this->uploadVideos($name, $img, $i, $folderName);
        }

        return $image_name;
    }

    protected function randomImageName($imageName, $image, $i): string
    {
        return Str::slug($imageName) . time() . '-' . $i . '.' . $image->getClientOriginalExtension();
    }

    /**
     * @param $name
     * @param $video
     * @param $i
     * @param $folderName
     * @return string
     */
    protected function uploadVideos($name, $video, $i, $folderName): string
    {
        $video_name = $this->randomImageName($name, $video, $i);
        $video->move(storage_path($this->path . $folderName), $video_name);
        return $video_name;
    }
}

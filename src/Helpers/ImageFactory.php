<?php

namespace Spatie\MediaLibrary\Helpers;

use Config;
use Spatie\Image\Image;

class ImageFactory
{
    public static function load(string $path): Image
    {
        return Image::load($path)
            ->useImageDriver(Config::get('medialibrary.image_driver'));
    }
}

<?php

namespace Spatie\MediaLibrary\ImageGenerators\FileTypes;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Conversion\Conversion;
use Spatie\MediaLibrary\ImageGenerators\BaseGenerator;

class Webp extends BaseGenerator
{
    public function convert($file, Conversion $conversion = null)
    {
        $pathToImageFile = pathinfo($file, PATHINFO_DIRNAME).'/'.pathinfo($file, PATHINFO_FILENAME).'.png';

        $image = imagecreatefromwebp($file);

        imagepng($image, $pathToImageFile, 9);

        imagedestroy($image);

        return $pathToImageFile;
    }

    public function requirementsAreInstalled()
    {
        if (! function_exists('imagecreatefromwebp')) {
            return false;
        }

        if (! function_exists('imagepng')) {
            return false;
        }

        if (! function_exists('imagedestroy')) {
            return false;
        }

        return true;
    }

    public function supportedExtensions()
    {
        return new Collection(['webp']);
    }

    public function supportedMimeTypes()
    {
        return new Collection(['image/webp']);
    }
}

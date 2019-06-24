<?php

namespace Spatie\MediaLibrary\Tests\Unit\PathGenerator;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media)
    {
        return md5($media->id).'/';
    }

    public function getPathForConversions(Media $media)
    {
        return $this->getPath($media).'c/';
    }

    public function getPathForResponsiveImages(Media $media)
    {
        return $this->getPath($media).'/cri/';
    }
}

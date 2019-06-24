<?php

namespace Spatie\MediaLibrary\ImageGenerators;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Models\Media;

abstract class BaseGenerator implements ImageGenerator
{
    public function canConvert(Media $media)
    {
        if (! $this->requirementsAreInstalled()) {
            return false;
        }

        if ($this->supportedExtensions()->contains(strtolower($media->extension))) {
            return true;
        }

        if ($this->supportedMimetypes()->contains(strtolower($media->mime_type))) {
            return true;
        }

        return false;
    }

    public function canHandleMime($mime = '')
    {
        return $this->supportedMimetypes()->contains($mime);
    }

    public function canHandleExtension($extension = '')
    {
        return $this->supportedExtensions()->contains($extension);
    }

    public function getType()
    {
        return strtolower(class_basename(static::class));
    }

    abstract public function requirementsAreInstalled();

    abstract public function supportedExtensions();

    abstract public function supportedMimetypes();
}

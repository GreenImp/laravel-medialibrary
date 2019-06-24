<?php

namespace Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;

use Spatie\MediaLibrary\Helpers\File;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;

class FileDoesNotExist extends FileCannotBeAdded
{
    public static function create($path)
    {
        return new static("File `{$path}` does not exist");
    }
}
